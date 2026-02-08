<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        $featuredPosts = Post::with(['user', 'category'])
            ->public()
            ->published()
            ->latest('published_at')
            ->limit(6)
            ->get();

        $categories = Category::approved()
            ->withCount(['posts' => fn ($q) => $q->public()->published()])
            ->orderByDesc('posts_count')
            ->limit(8)
            ->get();

        $popularTags = Tag::withCount('posts')
            ->orderByDesc('posts_count')
            ->limit(12)
            ->get();

        return Inertia::render('Welcome', [
            'featuredPosts' => $featuredPosts,
            'categories' => $categories,
            'popularTags' => $popularTags,
        ]);
    }
}
