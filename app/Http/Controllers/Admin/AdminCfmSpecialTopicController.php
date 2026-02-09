<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CfmSpecialTopic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminCfmSpecialTopicController extends Controller
{
    public function index(): Response
    {
        $topics = CfmSpecialTopic::withCount('weeks')
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('Admin/Cfm/SpecialTopics/Index', [
            'topics' => $topics,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Cfm/SpecialTopics/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:cfm_special_topics,name',
            'description' => 'nullable|string',
        ]);

        CfmSpecialTopic::create($validated);

        return redirect()->route('admin.cfm.special-topics.index')
            ->with('success', 'Special topic created successfully.');
    }

    public function show(CfmSpecialTopic $specialTopic): Response
    {
        $specialTopic->load(['weeks' => fn ($q) => $q->with('studyYear')->orderByDesc('start_date')]);

        return Inertia::render('Admin/Cfm/SpecialTopics/Show', [
            'topic' => $specialTopic,
        ]);
    }

    public function edit(CfmSpecialTopic $specialTopic): Response
    {
        return Inertia::render('Admin/Cfm/SpecialTopics/Edit', [
            'topic' => $specialTopic,
        ]);
    }

    public function update(Request $request, CfmSpecialTopic $specialTopic): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:cfm_special_topics,name,' . $specialTopic->id,
            'description' => 'nullable|string',
        ]);

        $specialTopic->update($validated);

        return back()->with('success', 'Special topic updated successfully.');
    }

    public function destroy(CfmSpecialTopic $specialTopic): RedirectResponse
    {
        $specialTopic->delete();

        return redirect()->route('admin.cfm.special-topics.index')
            ->with('success', 'Special topic deleted successfully.');
    }
}
