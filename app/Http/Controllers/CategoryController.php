<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        $categories = Category::approved()
            ->withCount(['posts' => fn ($q) => $q->public()->published()])
            ->orderBy('name')
            ->get();

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function show(Request $request, Category $category): Response
    {
        $posts = Post::with(['user', 'category', 'tags'])
            ->where('category_id', $category->id)
            ->visibleTo($request->user())
            ->published()
            ->latest('published_at')
            ->paginate(12);

        return Inertia::render('Categories/Show', [
            'category' => $category,
            'posts' => $posts,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name',
            'description' => 'nullable|string|max:500',
        ]);

        $category = new Category($validated);
        $category->user_id = $request->user()->id;
        $category->is_approved = false;
        $category->save();

        return back()->with('success', 'Category suggested successfully. It will be reviewed by an admin.');
    }
}
