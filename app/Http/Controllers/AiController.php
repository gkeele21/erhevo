<?php

namespace App\Http\Controllers;

use App\Services\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function __construct(
        protected AiService $aiService
    ) {}

    /**
     * Extract text from an uploaded image.
     */
    public function extractText(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|max:10240', // Max 10MB
        ]);

        $image = $request->file('image');
        $base64 = base64_encode(file_get_contents($image->getRealPath()));

        try {
            $result = $this->aiService->extractTextFromImage($base64);

            return response()->json([
                'success' => true,
                'text' => $result['text'],
                'usage' => $result['usage'],
            ]);
        } catch (\Exception $e) {
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
}
