<?php

namespace App\Http\Controllers;

use App\Enums\AuthorType;
use App\Enums\PostType;
use App\Enums\Visibility;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Services\NameAnonymizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    public function __construct(
        protected NameAnonymizer $nameAnonymizer
    ) {}

    public function index(Request $request): Response
    {
        $posts = Post::with(['user', 'category', 'tags'])
            ->visibleTo($request->user())
            ->published()
            ->when($request->type, fn ($q, $type) => $q->where('post_type', $type))
            ->when($request->category, fn ($q, $category) => $q->whereHas('category', fn ($q2) => $q2->where('slug', $category)))
            ->when($request->tag, fn ($q, $tag) => $q->whereHas('tags', fn ($q2) => $q2->where('slug', $tag)))
            ->when($request->search, fn ($q, $search) => $q->where(function ($q2) use ($search) {
                $q2->where('title', 'like', "%{$search}%")
                    ->orWhereHas('tags', fn ($q3) => $q3->where('name', 'like', "%{$search}%"))
                    ->orWhere('author_text', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($q3) => $q3->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('authorUser', fn ($q3) => $q3->where('name', 'like', "%{$search}%"));
            }))
            ->when($request->friends_only && $request->user(), fn ($q) => $q->whereIn('user_id', $request->user()->friendIds()))
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Posts/Index', [
            'posts' => $posts,
            'categories' => Category::approved()->orderBy('name')->get(),
            'postTypes' => collect(PostType::cases())->map(fn ($p) => [
                'value' => $p->value,
                'label' => $p->label(),
            ]),
            'filters' => $request->only(['category', 'tag', 'search', 'friends_only', 'type']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Posts/Create', [
            'categories' => Category::approved()->orderBy('name')->get(),
            'postTypes' => collect(PostType::cases())->map(fn ($p) => [
                'value' => $p->value,
                'label' => $p->label(),
                'description' => $p->description(),
                'icon' => $p->icon(),
            ]),
            'visibilityOptions' => collect(Visibility::cases())->map(fn ($v) => [
                'value' => $v->value,
                'label' => $v->label(),
                'description' => $v->description(),
            ]),
            'authorTypes' => collect(AuthorType::cases())->map(fn ($a) => [
                'value' => $a->value,
                'label' => $a->label(),
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'post_type' => 'required|in:story,thought,note,quote',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'cover_image' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'author_type' => 'required|in:self,text,user',
            'author_text' => 'nullable|required_if:author_type,text|string|max:255',
            'author_user_id' => 'nullable|required_if:author_type,user|exists:users,id',
            'visibility' => 'required|in:public,private,friends',
            'hide_creator' => 'boolean',
            'hide_author' => 'boolean',
            'anonymize_names' => 'boolean',
            'name_mappings' => 'nullable|array',
            'publish' => 'boolean',
        ]);

        $post = new Post($validated);
        $post->user_id = $request->user()->id;

        if ($validated['anonymize_names'] ?? false) {
            $result = $this->nameAnonymizer->anonymize(
                $validated['content'],
                $validated['name_mappings'] ?? null
            );
            $post->content_anonymized = $result['content'];
            $post->name_mappings = $result['mappings'];
        }

        if ($validated['publish'] ?? false) {
            $post->published_at = now();
        }

        $post->save();

        if (! empty($validated['tags'])) {
            $post->syncTags($validated['tags']);
        }

        return redirect()->route('posts.show', $post)
            ->with('success', 'Post created successfully.');
    }

    public function show(Post $post): Response
    {
        Gate::authorize('view', $post);

        $post->load(['user', 'authorUser', 'category', 'tags']);

        return Inertia::render('Posts/Show', [
            'post' => $post,
            'canEdit' => $post->user_id === auth()->id(),
        ]);
    }

    public function edit(Post $post): Response
    {
        Gate::authorize('update', $post);

        $post->load(['category', 'tags']);

        return Inertia::render('Posts/Edit', [
            'post' => $post,
            'categories' => Category::approved()->orderBy('name')->get(),
            'postTypes' => collect(PostType::cases())->map(fn ($p) => [
                'value' => $p->value,
                'label' => $p->label(),
                'description' => $p->description(),
                'icon' => $p->icon(),
            ]),
            'visibilityOptions' => collect(Visibility::cases())->map(fn ($v) => [
                'value' => $v->value,
                'label' => $v->label(),
                'description' => $v->description(),
            ]),
            'authorTypes' => collect(AuthorType::cases())->map(fn ($a) => [
                'value' => $a->value,
                'label' => $a->label(),
            ]),
        ]);
    }

    public function update(Request $request, Post $post)
    {
        Gate::authorize('update', $post);

        $validated = $request->validate([
            'post_type' => 'required|in:story,thought,note,quote',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'cover_image' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'author_type' => 'required|in:self,text,user',
            'author_text' => 'nullable|required_if:author_type,text|string|max:255',
            'author_user_id' => 'nullable|required_if:author_type,user|exists:users,id',
            'visibility' => 'required|in:public,private,friends',
            'hide_creator' => 'boolean',
            'hide_author' => 'boolean',
            'anonymize_names' => 'boolean',
            'name_mappings' => 'nullable|array',
            'publish' => 'boolean',
        ]);

        $post->fill($validated);

        if ($validated['anonymize_names'] ?? false) {
            $result = $this->nameAnonymizer->anonymize(
                $validated['content'],
                $validated['name_mappings'] ?? $post->name_mappings
            );
            $post->content_anonymized = $result['content'];
            $post->name_mappings = $result['mappings'];
        } else {
            $post->content_anonymized = null;
            $post->name_mappings = null;
        }

        if (($validated['publish'] ?? false) && ! $post->published_at) {
            $post->published_at = now();
        }

        $post->save();

        if (isset($validated['tags'])) {
            $post->syncTags($validated['tags']);
        }

        return redirect()->route('posts.show', $post)
            ->with('success', 'Post updated successfully.');
    }

    public function destroy(Post $post)
    {
        Gate::authorize('delete', $post);

        $post->delete();

        return redirect()->route('dashboard')
            ->with('success', 'Post deleted successfully.');
    }
}
