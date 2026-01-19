<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Story;
use App\Models\Tag;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        $featuredStories = Story::with(['user', 'category'])
            ->public()
            ->published()
            ->latest('published_at')
            ->limit(6)
            ->get();

        $categories = Category::approved()
            ->withCount(['stories' => fn ($q) => $q->public()->published()])
            ->orderByDesc('stories_count')
            ->limit(8)
            ->get();

        $popularTags = Tag::withCount('stories')
            ->orderByDesc('stories_count')
            ->limit(12)
            ->get();

        return Inertia::render('Welcome', [
            'featuredStories' => $featuredStories,
            'categories' => $categories,
            'popularTags' => $popularTags,
        ]);
    }
}
