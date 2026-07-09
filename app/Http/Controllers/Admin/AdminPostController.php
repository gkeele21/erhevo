<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminPostController extends Controller
{
    public function index(Request $request): Response
    {
        $posts = Post::query()
            ->with(['user', 'author', 'tags'])
            ->when($request->search, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%{$s}%")
                ->orWhereHas('author', fn ($a) => $a->search($s))
                ->orWhereHas('user', fn ($u) => $u->where('first_name', 'like', "%{$s}%")->orWhere('last_name', 'like', "%{$s}%"))))
            ->when($request->type, fn ($q, $t) => $q->where('post_type', $t))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Posts/Index', [
            'posts' => $posts,
            'filters' => $request->only(['search', 'type']),
            'postTypes' => collect(PostType::cases())->map(fn ($p) => [
                'value' => $p->value,
                'label' => $p->label(),
            ]),
        ]);
    }

    public function destroy(Post $post): RedirectResponse
    {
        $post->delete();

        return back()->with('success', 'Post deleted.');
    }
}
