<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\ChurchCalling;
use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuthorController extends Controller
{
    public function index(Request $request): Response
    {
        $authors = Author::query()
            ->withCount('posts')
            ->when($request->search, fn ($q, $search) => $q->search($search))
            ->orderBy('last_name')
            ->orderBy('display_name')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Authors/Index', [
            'authors' => $authors,
            'filters' => $request->only(['search']),
            'canMerge' => (bool) $request->user()?->isAdmin(),
        ]);
    }

    public function show(Request $request, Author $author): Response
    {
        $author->load('calling', 'user');

        $posts = $author->posts()
            ->with(['tags', 'calling'])
            ->visibleTo($request->user())
            ->published()
            ->when($request->calling, fn ($q, $calling) => $q->where('church_calling_id', $calling))
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        // Callings this author actually has content under, for the filter.
        $usedCallingIds = $author->posts()
            ->visibleTo($request->user())
            ->published()
            ->whereNotNull('church_calling_id')
            ->distinct()
            ->pluck('church_calling_id');

        $callings = ChurchCalling::whereIn('id', $usedCallingIds)
            ->orderBy('name')
            ->get()
            ->map(fn (ChurchCalling $c) => ['id' => $c->id, 'label' => $c->full_title]);

        return Inertia::render('Authors/Show', [
            'author' => [
                'id' => $author->id,
                'slug' => $author->slug,
                'full_name' => $author->full_name,
                'current_calling' => $author->calling?->full_title,
                'is_user' => (bool) $author->user_id,
                'notes' => $author->notes,
            ],
            'posts' => $posts,
            'callings' => $callings,
            'filters' => $request->only(['calling']),
        ]);
    }

    /**
     * Merge one author into another: repoint all their content and delete the
     * duplicate. Admin-only, since it affects everyone's content.
     */
    public function merge(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'from_id' => 'required|exists:authors,id',
            'into_id' => 'required|different:from_id|exists:authors,id',
        ]);

        $from = Author::findOrFail($data['from_id']);
        $into = Author::findOrFail($data['into_id']);

        Post::where('author_id', $from->id)->update(['author_id' => $into->id]);

        // Preserve a user link if the surviving author lacks one.
        if (! $into->user_id && $from->user_id) {
            $into->user_id = $from->user_id;
            $into->save();
        }

        $from->delete();

        return back()->with('success', "Merged “{$from->full_name}” into “{$into->full_name}”.");
    }
}
