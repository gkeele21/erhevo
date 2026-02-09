<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CfmPublisher;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminCfmPublisherController extends Controller
{
    public function index(Request $request)
    {
        $query = CfmPublisher::withCount('content');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('website_url', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status === 'verified') {
                $query->where('is_verified', true);
            }
        }

        $publishers = $query->orderBy('name')->paginate(20)->withQueryString();

        return Inertia::render('Admin/Cfm/Publishers/Index', [
            'publishers' => $publishers,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Cfm/Publishers/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:cfm_publishers,slug',
            'description' => 'nullable|string',
            'website_url' => 'nullable|url|max:255',
            'logo_url' => 'nullable|url|max:255',
            'social_links' => 'nullable|array',
            'social_links.youtube' => 'nullable|url',
            'social_links.instagram' => 'nullable|url',
            'social_links.facebook' => 'nullable|url',
            'social_links.twitter' => 'nullable|url',
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
        ]);

        // Filter out empty social links
        if (isset($validated['social_links'])) {
            $validated['social_links'] = array_filter($validated['social_links']);
        }

        CfmPublisher::create($validated);

        return redirect()->route('admin.cfm.publishers.index')
            ->with('success', 'Publisher created successfully.');
    }

    public function show(CfmPublisher $publisher)
    {
        $publisher->load(['content' => function ($query) {
            $query->with('cfmWeek')->orderByDesc('created_at');
        }]);
        $publisher->loadCount('content');

        return Inertia::render('Admin/Cfm/Publishers/Show', [
            'publisher' => $publisher,
        ]);
    }

    public function edit(CfmPublisher $publisher)
    {
        return Inertia::render('Admin/Cfm/Publishers/Edit', [
            'publisher' => $publisher,
        ]);
    }

    public function update(Request $request, CfmPublisher $publisher)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:cfm_publishers,slug,' . $publisher->id,
            'description' => 'nullable|string',
            'website_url' => 'nullable|url|max:255',
            'logo_url' => 'nullable|url|max:255',
            'social_links' => 'nullable|array',
            'social_links.youtube' => 'nullable|url',
            'social_links.instagram' => 'nullable|url',
            'social_links.facebook' => 'nullable|url',
            'social_links.twitter' => 'nullable|url',
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
        ]);

        // Filter out empty social links
        if (isset($validated['social_links'])) {
            $validated['social_links'] = array_filter($validated['social_links']);
        }

        $publisher->update($validated);

        return redirect()->route('admin.cfm.publishers.show', $publisher)
            ->with('success', 'Publisher updated successfully.');
    }

    public function destroy(CfmPublisher $publisher)
    {
        $publisher->delete();

        return redirect()->route('admin.cfm.publishers.index')
            ->with('success', 'Publisher deleted successfully.');
    }

    public function toggleVerified(CfmPublisher $publisher)
    {
        $publisher->update(['is_verified' => !$publisher->is_verified]);

        return back()->with('success', $publisher->is_verified ? 'Publisher verified.' : 'Publisher unverified.');
    }

    public function toggleActive(CfmPublisher $publisher)
    {
        $publisher->update(['is_active' => !$publisher->is_active]);

        return back()->with('success', $publisher->is_active ? 'Publisher activated.' : 'Publisher deactivated.');
    }
}
