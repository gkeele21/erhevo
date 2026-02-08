<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\StoryEditToken;
use App\Services\NameAnonymizer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SharedStoryController extends Controller
{
    public function __construct(
        protected NameAnonymizer $nameAnonymizer
    ) {}

    public function edit(string $token)
    {
        $editToken = StoryEditToken::where('token', $token)
            ->with('story.tags', 'story.category')
            ->first();

        if (!$editToken) {
            return Inertia::render('Posts/SharedExpired', [
                'message' => 'This edit link is not valid.',
            ]);
        }

        if (!$editToken->isValid()) {
            return Inertia::render('Posts/SharedExpired', [
                'message' => $editToken->is_active
                    ? 'This edit link has expired.'
                    : 'This edit link has been revoked.',
            ]);
        }

        $editToken->recordUsage(request()->ip());

        return Inertia::render('Posts/SharedEdit', [
            'story' => [
                'title' => $editToken->story->title,
                'content' => $editToken->story->content,
                'excerpt' => $editToken->story->excerpt,
                'category_id' => $editToken->story->category_id,
                'tags' => $editToken->story->tags->pluck('name'),
            ],
            'token' => $token,
            'categories' => Category::approved()->orderBy('name')->get(),
            'expiresAt' => $editToken->expires_at->toIso8601String(),
        ]);
    }

    public function update(Request $request, string $token)
    {
        $editToken = StoryEditToken::where('token', $token)
            ->with('story')
            ->first();

        if (!$editToken || !$editToken->isValid()) {
            abort(403, 'This edit link is no longer valid.');
        }

        $story = $editToken->story;

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'category_id' => 'nullable|exists:categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        $story->title = $validated['title'];
        $story->content = $validated['content'];
        $story->excerpt = $validated['excerpt'] ?? null;
        $story->category_id = $validated['category_id'] ?? null;

        if ($story->anonymize_names) {
            $result = $this->nameAnonymizer->anonymize(
                $validated['content'],
                $story->name_mappings
            );
            $story->content_anonymized = $result['content'];
            $story->name_mappings = $result['mappings'];
        }

        $story->save();

        if (isset($validated['tags'])) {
            $story->syncTags($validated['tags']);
        }

        $editToken->recordUsage($request->ip());

        return redirect()->route('posts.shared.edit', $token)
            ->with('success', 'Post updated successfully.');
    }
}
