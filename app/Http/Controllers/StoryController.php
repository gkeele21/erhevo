<?php

namespace App\Http\Controllers;

use App\Enums\AuthorType;
use App\Enums\Visibility;
use App\Models\Category;
use App\Models\Story;
use App\Models\Tag;
use App\Models\User;
use App\Services\NameAnonymizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class StoryController extends Controller
{
    public function __construct(
        protected NameAnonymizer $nameAnonymizer
    ) {}

    public function index(Request $request): Response
    {
        $stories = Story::with(['user', 'category', 'tags'])
            ->visibleTo($request->user())
            ->published()
            ->when($request->category, fn ($q, $category) => $q->whereHas('category', fn ($q2) => $q2->where('slug', $category)))
            ->when($request->tag, fn ($q, $tag) => $q->whereHas('tags', fn ($q2) => $q2->where('slug', $tag)))
            ->when($request->search, fn ($q, $search) => $q->where('title', 'like', "%{$search}%"))
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Stories/Index', [
            'stories' => $stories,
            'categories' => Category::approved()->orderBy('name')->get(),
            'filters' => $request->only(['category', 'tag', 'search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Stories/Create', [
            'categories' => Category::approved()->orderBy('name')->get(),
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

        $story = new Story($validated);
        $story->user_id = $request->user()->id;

        if ($validated['anonymize_names'] ?? false) {
            $result = $this->nameAnonymizer->anonymize(
                $validated['content'],
                $validated['name_mappings'] ?? null
            );
            $story->content_anonymized = $result['content'];
            $story->name_mappings = $result['mappings'];
        }

        if ($validated['publish'] ?? false) {
            $story->published_at = now();
        }

        $story->save();

        if (! empty($validated['tags'])) {
            $story->syncTags($validated['tags']);
        }

        return redirect()->route('stories.show', $story)
            ->with('success', 'Story created successfully.');
    }

    public function show(Story $story): Response
    {
        Gate::authorize('view', $story);

        $story->load(['user', 'authorUser', 'category', 'tags']);

        return Inertia::render('Stories/Show', [
            'story' => $story,
            'canEdit' => $story->user_id === auth()->id(),
        ]);
    }

    public function edit(Story $story): Response
    {
        Gate::authorize('update', $story);

        $story->load(['category', 'tags']);

        return Inertia::render('Stories/Edit', [
            'story' => $story,
            'categories' => Category::approved()->orderBy('name')->get(),
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

    public function update(Request $request, Story $story)
    {
        Gate::authorize('update', $story);

        $validated = $request->validate([
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

        $story->fill($validated);

        if ($validated['anonymize_names'] ?? false) {
            $result = $this->nameAnonymizer->anonymize(
                $validated['content'],
                $validated['name_mappings'] ?? $story->name_mappings
            );
            $story->content_anonymized = $result['content'];
            $story->name_mappings = $result['mappings'];
        } else {
            $story->content_anonymized = null;
            $story->name_mappings = null;
        }

        if (($validated['publish'] ?? false) && ! $story->published_at) {
            $story->published_at = now();
        }

        $story->save();

        if (isset($validated['tags'])) {
            $story->syncTags($validated['tags']);
        }

        return redirect()->route('stories.show', $story)
            ->with('success', 'Story updated successfully.');
    }

    public function destroy(Story $story)
    {
        Gate::authorize('delete', $story);

        $story->delete();

        return redirect()->route('dashboard')
            ->with('success', 'Story deleted successfully.');
    }
}
