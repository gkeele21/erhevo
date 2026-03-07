<?php

namespace App\Http\Controllers;

use App\Models\UserCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UserCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $categories = $request->user()
            ->userCategories()
            ->root()
            ->with('children')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('UserCategories/Index', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'parent_id' => 'nullable|exists:user_categories,id',
        ]);

        if ($validated['parent_id']) {
            $parent = UserCategory::find($validated['parent_id']);
            Gate::authorize('update', $parent);

            if (!$parent->isRoot()) {
                return back()->withErrors(['parent_id' => 'Cannot create a child of a child category.']);
            }
        }

        $maxSortOrder = $request->user()
            ->userCategories()
            ->where('parent_id', $validated['parent_id'] ?? null)
            ->max('sort_order') ?? 0;

        $request->user()->userCategories()->create([
            ...$validated,
            'sort_order' => $maxSortOrder + 1,
        ]);

        return back()->with('success', 'Category created successfully.');
    }

    public function update(Request $request, UserCategory $userCategory)
    {
        Gate::authorize('update', $userCategory);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $userCategory->update($validated);

        return back()->with('success', 'Category updated successfully.');
    }

    public function destroy(UserCategory $userCategory)
    {
        Gate::authorize('delete', $userCategory);

        $userCategory->delete();

        return back()->with('success', 'Category deleted successfully.');
    }
}
