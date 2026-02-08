<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\PostEditToken;
use App\Services\NameAnonymizer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SharedPostController extends Controller
{
    public function __construct(
        protected NameAnonymizer $nameAnonymizer
    ) {}

    public function edit(string $token)
    {
        $editToken = PostEditToken::where('token', $token)
            ->with('post.tags', 'post.category')
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
            'post' => [
                'title' => $editToken->post->title,
                'content' => $editToken->post->content,
                'excerpt' => $editToken->post->excerpt,
                'category_id' => $editToken->post->category_id,
                'tags' => $editToken->post->tags->pluck('name'),
            ],
            'token' => $token,
            'categories' => Category::approved()->orderBy('name')->get(),
            'expiresAt' => $editToken->expires_at->toIso8601String(),
        ]);
    }

    public function update(Request $request, string $token)
    {
        $editToken = PostEditToken::where('token', $token)
            ->with('post')
            ->first();

        if (!$editToken || !$editToken->isValid()) {
            abort(403, 'This edit link is no longer valid.');
        }

        $post = $editToken->post;

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'category_id' => 'nullable|exists:categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        $post->title = $validated['title'];
        $post->content = $validated['content'];
        $post->excerpt = $validated['excerpt'] ?? null;
        $post->category_id = $validated['category_id'] ?? null;

        if ($post->anonymize_names) {
            $result = $this->nameAnonymizer->anonymize(
                $validated['content'],
                $post->name_mappings
            );
            $post->content_anonymized = $result['content'];
            $post->name_mappings = $result['mappings'];
        }

        $post->save();

        if (isset($validated['tags'])) {
            $post->syncTags($validated['tags']);
        }

        $editToken->recordUsage($request->ip());

        return redirect()->route('posts.shared.edit', $token)
            ->with('success', 'Post updated successfully.');
    }
}
