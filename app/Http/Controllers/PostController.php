<?php

namespace App\Http\Controllers;

use App\Enums\AuthorType;
use App\Enums\PostType;
use App\Enums\Visibility;
use App\Models\Author;
use App\Models\Category;
use App\Models\CfmStudyYear;
use App\Models\CfmWeek;
use App\Models\ChurchCalling;
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
            ->when($request->calling, fn ($q, $calling) => $q->where('church_calling_id', $calling))
            ->when($request->search, fn ($q, $search) => $q->where(function ($q2) use ($search) {
                $q2->where('title', 'like', "%{$search}%")
                    ->orWhereHas('tags', fn ($q3) => $q3->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('author', fn ($q3) => $q3->search($search))
                    ->orWhereHas('user', fn ($q3) => $q3->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%"));
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
                'plural' => $p->pluralLabel(),
            ]),
            'churchCallings' => $this->churchCallings(),
            'filters' => $request->only(['category', 'tag', 'search', 'friends_only', 'type', 'calling']),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Posts/Create', [
            'categories' => Category::approved()->orderBy('name')->get(),
            'userCategories' => $request->user()->userCategories()->root()->with('children')->orderBy('sort_order')->get(),
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
            'cfmWeeks' => $this->getCfmWeeksForSelect(),
            'currentCfmWeek' => CfmWeek::current()->with('studyYear')->first(),
            'churchCallings' => $this->churchCallings(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'post_type' => 'required|in:story,thought,note,quote,meeting_notes',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'cover_image' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'user_category_id' => 'nullable|exists:user_categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'cfm_week_ids' => 'nullable|array',
            'cfm_week_ids.*' => 'exists:cfm_weeks,id',
            'author_type' => 'required|in:self,author',
            'author_text' => 'nullable|string|max:255',
            'author_id' => 'nullable|exists:authors,id',
            'church_calling_id' => 'nullable|exists:church_callings,id',
            'visibility' => 'required|in:public,private,friends',
            'hide_creator' => 'boolean',
            'hide_author' => 'boolean',
            'anonymize_names' => 'boolean',
            'name_mappings' => 'nullable|array',
            'date_given' => 'nullable|date',
            'publish' => 'boolean',
        ]);

        $post = new Post($validated);
        $post->user_id = $request->user()->id;
        $post->author_id = $this->resolveAuthorId($validated, $post);

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

        $post->cfmWeeks()->sync($validated['cfm_week_ids'] ?? []);

        return redirect()->route('posts.show', $post)
            ->with('success', 'Post created successfully.');
    }

    public function show(Post $post): Response
    {
        Gate::authorize('view', $post);

        $post->load(['user', 'author', 'category', 'tags']);

        // Lessons that reference this post (e.g. via a Quote block), limited to
        // those the viewer is allowed to see.
        $usedInLessons = $post->lessonItems()
            ->with('lesson')
            ->get()
            ->pluck('lesson')
            ->filter()
            ->unique('id')
            ->filter(fn ($lesson) => $lesson->isVisibleTo(auth()->user()))
            ->map(fn ($lesson) => ['title' => $lesson->title, 'slug' => $lesson->slug])
            ->values();

        return Inertia::render('Posts/Show', [
            'post' => $post,
            'canEdit' => $post->user_id === auth()->id(),
            'usedInLessons' => $usedInLessons,
        ]);
    }

    public function edit(Request $request, Post $post): Response
    {
        Gate::authorize('update', $post);

        $post->load(['category', 'userCategory', 'tags', 'cfmWeeks', 'author']);

        return Inertia::render('Posts/Edit', [
            'post' => $post,
            'categories' => Category::approved()->orderBy('name')->get(),
            'userCategories' => $request->user()->userCategories()->root()->with('children')->orderBy('sort_order')->get(),
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
            'cfmWeeks' => $this->getCfmWeeksForSelect(),
            'currentCfmWeek' => CfmWeek::current()->with('studyYear')->first(),
            'churchCallings' => $this->churchCallings(),
        ]);
    }

    public function update(Request $request, Post $post)
    {
        Gate::authorize('update', $post);

        $validated = $request->validate([
            'post_type' => 'required|in:story,thought,note,quote,meeting_notes',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'cover_image' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'user_category_id' => 'nullable|exists:user_categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'cfm_week_ids' => 'nullable|array',
            'cfm_week_ids.*' => 'exists:cfm_weeks,id',
            'author_type' => 'required|in:self,author',
            'author_text' => 'nullable|string|max:255',
            'author_id' => 'nullable|exists:authors,id',
            'church_calling_id' => 'nullable|exists:church_callings,id',
            'visibility' => 'required|in:public,private,friends',
            'hide_creator' => 'boolean',
            'hide_author' => 'boolean',
            'anonymize_names' => 'boolean',
            'name_mappings' => 'nullable|array',
            'date_given' => 'nullable|date',
            'publish' => 'boolean',
        ]);

        $post->fill($validated);
        $post->author_id = $this->resolveAuthorId($validated, $post);

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

        $post->cfmWeeks()->sync($validated['cfm_week_ids'] ?? []);

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

    /**
     * Search for existing author names (for autocomplete).
     */
    public function searchAuthors(Request $request)
    {
        $query = trim((string) $request->input('q', ''));

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $authors = Author::search($query)
            ->with('calling')
            ->orderBy('last_name')
            ->orderBy('display_name')
            ->limit(10)
            ->get()
            ->map(fn (Author $author) => [
                'id' => $author->id,
                'full_name' => $author->full_name,
                'calling' => $author->calling?->name,
                'is_user' => (bool) $author->user_id,
            ]);

        return response()->json($authors);
    }

    /**
     * Get CFM weeks for the select dropdown, grouped by study year.
     */
    protected function getCfmWeeksForSelect(): array
    {
        return CfmWeek::with('studyYear')
            ->whereHas('studyYear', fn ($q) => $q->where('year', '>=', now()->year - 2))
            ->orderByDesc('start_date')
            ->get()
            ->toArray();
    }

    /**
     * Resolve the first-class author for a post from the submitted attribution.
     * Self/User map to the relevant user's Author; Text prefers a chosen author
     * entity, else find-or-creates one from the typed name.
     */
    protected function resolveAuthorId(array $validated, Post $post): ?int
    {
        return match ($post->author_type) {
            AuthorType::Self => $post->user ? Author::forUser($post->user)->id : null,
            AuthorType::Author => ! empty($validated['author_id'])
                ? (int) $validated['author_id']
                : (! empty($validated['author_text']) ? Author::findOrCreateByName($validated['author_text'])->id : null),
            default => null,
        };
    }

    /**
     * Active church callings for the calling picker.
     */
    protected function churchCallings(): array
    {
        return ChurchCalling::with('organization')
            ->orderBy('church_organization_id')
            ->orderBy('name')
            ->get()
            ->map(fn (ChurchCalling $c) => ['id' => $c->id, 'label' => $c->display_label])
            ->toArray();
    }
}
