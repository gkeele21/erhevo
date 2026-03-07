<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminCategoryController extends Controller
{
    public function index(): Response
    {
        $categories = Category::with(['user', 'parent', 'children'])
            ->orderByDesc('created_at')
            ->paginate(20);

        $parentCategories = Category::root()
            ->approved()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Admin/Categories/Index', [
            'categories' => $categories,
            'parentCategories' => $parentCategories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name',
            'description' => 'nullable|string|max:500',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        // Enforce 2-level hierarchy
        if ($validated['parent_id']) {
            $parent = Category::find($validated['parent_id']);
            if (!$parent->isRoot()) {
                return back()->withErrors(['parent_id' => 'Cannot create a child of a child category.']);
            }
        }

        $maxSortOrder = Category::where('parent_id', $validated['parent_id'] ?? null)->max('sort_order') ?? 0;

        Category::create([
            ...$validated,
            'sort_order' => $maxSortOrder + 1,
            'user_id' => null,
            'is_approved' => true,
        ]);

        return back()->with('success', 'Category created successfully.');
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name,'.$category->id,
            'description' => 'nullable|string|max:500',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        // Prevent setting parent to self or own children
        if ($validated['parent_id'] == $category->id) {
            return back()->withErrors(['parent_id' => 'A category cannot be its own parent.']);
        }

        // Enforce 2-level hierarchy
        if ($validated['parent_id']) {
            $parent = Category::find($validated['parent_id']);
            if (!$parent->isRoot()) {
                return back()->withErrors(['parent_id' => 'Cannot create a child of a child category.']);
            }
            // If this category has children, it can't become a child itself
            if ($category->children()->exists()) {
                return back()->withErrors(['parent_id' => 'A category with children cannot become a subcategory.']);
            }
        }

        $category->update($validated);

        return back()->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return back()->with('success', 'Category deleted successfully.');
    }

    public function approve(Category $category)
    {
        $category->update(['is_approved' => true]);

        return back()->with('success', 'Category approved successfully.');
    }

    public function reject(Category $category)
    {
        $category->delete();

        return back()->with('success', 'Category rejected and deleted.');
    }
}
