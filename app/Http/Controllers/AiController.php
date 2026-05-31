<?php

namespace App\Http\Controllers;

use App\Services\AiService;
use App\Services\ScriptureReferenceParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AiController extends Controller
{
    public function __construct(
        protected AiService $aiService,
        protected ScriptureReferenceParser $scriptureParser
    ) {}

    /**
     * Extract text from an uploaded image.
     */
    public function extractText(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|file|mimes:jpeg,jpg,png,gif,webp,heic,heif|max:10240', // Max 10MB
        ]);

        $image = $request->file('image');
        $mimeType = $image->getMimeType();
        $imagePath = $image->getRealPath();

        // Convert HEIC/HEIF to JPEG since OpenAI doesn't support them
        if (in_array($mimeType, ['image/heic', 'image/heif'])) {
            // Use macOS sips command to convert HEIC to JPEG
            $tempJpeg = sys_get_temp_dir() . '/' . uniqid('heic_') . '.jpg';
            $command = sprintf(
                'sips -s format jpeg %s --out %s 2>&1',
                escapeshellarg($imagePath),
                escapeshellarg($tempJpeg)
            );
            exec($command, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($tempJpeg)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to convert HEIC image. Please convert to JPEG or PNG first.',
                ], 422);
            }

            $base64 = base64_encode(file_get_contents($tempJpeg));
            unlink($tempJpeg); // Clean up temp file
            $mimeType = 'image/jpeg';
        } else {
            $base64 = base64_encode(file_get_contents($imagePath));
        }

        try {
            $result = $this->aiService->extractTextFromImage($base64, false, $mimeType);

            return response()->json([
                'success' => true,
                'text' => $result['text'],
                'usage' => $result['usage'],
            ]);
        } catch (\Exception $e) {
            Log::error('AI Extract Text Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to extract text from image. Please try again.',
            ], 500);
        }
    }

    /**
     * Generate an excerpt for content.
     */
    public function generateExcerpt(Request $request): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|min:50',
            'max_length' => 'nullable|integer|min:50|max:500',
        ]);

        try {
            $excerpt = $this->aiService->generateExcerpt(
                $request->input('content'),
                $request->input('max_length', 200)
            );

            return response()->json([
                'success' => true,
                'excerpt' => $excerpt,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate excerpt. Please try again.',
            ], 500);
        }
    }

    /**
     * Suggest tags for content.
     */
    public function suggestTags(Request $request): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|min:20',
            'max_tags' => 'nullable|integer|min:1|max:10',
        ]);

        try {
            $tags = $this->aiService->suggestTags(
                $request->input('content'),
                $request->input('max_tags', 5)
            );

            return response()->json([
                'success' => true,
                'tags' => $tags,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to suggest tags. Please try again.',
            ], 500);
        }
    }

    /**
     * Suggest scripture references for content.
     */
    public function suggestScriptures(Request $request): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|min:20',
            'max_suggestions' => 'nullable|integer|min:1|max:10',
        ]);

        try {
            $includeLdsScriptures = $request->user()->show_lds_content;

            $suggestions = $this->aiService->suggestScriptures(
                $request->input('content'),
                $request->input('max_suggestions', 5),
                $includeLdsScriptures
            );

            // Validate and enrich suggestions with chapter IDs
            $validatedSuggestions = [];
            foreach ($suggestions as $suggestion) {
                $parsed = $this->scriptureParser->parseToChapterIds($suggestion['reference'] ?? '');
                $validatedSuggestions[] = [
                    'reference' => $suggestion['reference'] ?? '',
                    'reason' => $suggestion['reason'] ?? '',
                    'valid' => $parsed !== null,
                    'chapter_data' => $parsed,
                ];
            }

            return response()->json([
                'success' => true,
                'suggestions' => $validatedSuggestions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to suggest scriptures. Please try again.',
            ], 500);
        }
    }

    /**
     * Generate personalized writing prompts.
     */
    public function generateWritingPrompts(Request $request): JsonResponse
    {
        $request->validate([
            'context' => 'nullable|string|max:500',
        ]);

        try {
            $user = Auth::user();

            // Gather user's recent posts for context
            $recentPosts = $user->posts()
                ->latest()
                ->limit(10)
                ->pluck('title')
                ->toArray();

            // Gather user's common tags
            $userTags = $user->posts()
                ->with('tags')
                ->get()
                ->pluck('tags')
                ->flatten()
                ->pluck('name')
                ->countBy()
                ->sortDesc()
                ->keys()
                ->take(10)
                ->toArray();

            $prompts = $this->aiService->generateWritingPrompts(
                $recentPosts,
                $userTags,
                $request->input('context')
            );

            return response()->json([
                'success' => true,
                'prompts' => $prompts,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate prompts. Please try again.',
            ], 500);
        }
    }

    /**
     * Analyze content insights across user's posts.
     */
    public function analyzeInsights(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            // Get user's published posts
            $posts = $user->posts()
                ->whereNotNull('published_at')
                ->latest()
                ->limit(20)
                ->get(['title', 'content', 'excerpt'])
                ->map(fn ($post) => $post->excerpt ?: substr(strip_tags($post->content), 0, 200))
                ->toArray();

            $postCount = count($posts);

            if ($postCount < 3) {
                return response()->json([
                    'success' => true,
                    'insights' => null,
                    'post_count' => $postCount,
                    'message' => 'At least 3 published posts are needed for insights.',
                ]);
            }

            $insights = $this->aiService->analyzeContentInsights($posts);

            return response()->json([
                'success' => true,
                'insights' => $insights,
                'post_count' => $postCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to analyze insights. Please try again.',
            ], 500);
        }
    }

    /**
     * Analyze content for privacy sensitivity.
     */
    public function analyzeContentSensitivity(Request $request): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|min:20',
        ]);

        try {
            $analysis = $this->aiService->analyzeContentSensitivity(
                $request->input('content')
            );

            return response()->json([
                'success' => true,
                'analysis' => $analysis,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to analyze content. Please try again.',
            ], 500);
        }
    }

    /**
     * Suggest a category for content.
     */
    public function suggestCategory(Request $request): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|min:20',
            'type' => 'required|in:user,public',
            'existing_categories' => 'nullable|array',
        ]);

        try {
            $suggestion = $this->aiService->suggestCategory(
                $request->input('content'),
                $request->input('existing_categories', []),
                $request->input('type')
            );

            return response()->json([
                'success' => true,
                'suggestion' => $suggestion,
            ]);
        } catch (\Exception $e) {
            Log::error('AI Suggest Category Error', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to suggest category. Please try again.',
            ], 500);
        }
    }
}
