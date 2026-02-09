<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CfmSpecialTopic;
use App\Models\CfmStudyYear;
use App\Models\CfmWeek;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminCfmWeekController extends Controller
{
    public function index(Request $request): Response
    {
        $weeks = CfmWeek::with('studyYear')
            ->when($request->study_year_id, fn ($q, $id) => $q->where('study_year_id', $id))
            ->orderByDesc('start_date')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Cfm/Weeks/Index', [
            'weeks' => $weeks,
            'studyYears' => CfmStudyYear::orderByDesc('year')->get(),
            'filters' => $request->only(['study_year_id']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Cfm/Weeks/Create', [
            'studyYears' => CfmStudyYear::orderByDesc('year')->get(),
            'specialTopics' => CfmSpecialTopic::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'study_year_id' => 'required|exists:cfm_study_years,id',
            'week_number' => 'required|integer|min:1|max:53',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_special_topic' => 'boolean',
            'special_topic_ids' => 'nullable|array',
            'special_topic_ids.*' => 'exists:cfm_special_topics,id',
        ]);

        $week = CfmWeek::create([
            'study_year_id' => $validated['study_year_id'],
            'week_number' => $validated['week_number'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title'] . '-' . $validated['week_number']),
            'description' => $validated['description'] ?? null,
            'is_special_topic' => $validated['is_special_topic'] ?? false,
        ]);

        if (!empty($validated['special_topic_ids'])) {
            $week->specialTopics()->attach($validated['special_topic_ids']);
        }

        return redirect()->route('admin.cfm.weeks.index')
            ->with('success', 'Week created successfully.');
    }

    public function show(CfmWeek $week): Response
    {
        $week->load(['studyYear', 'specialTopics', 'chapters.book']);

        return Inertia::render('Admin/Cfm/Weeks/Show', [
            'week' => $week,
        ]);
    }

    public function edit(CfmWeek $week): Response
    {
        $week->load(['studyYear', 'specialTopics', 'chapters']);

        return Inertia::render('Admin/Cfm/Weeks/Edit', [
            'week' => $week,
            'studyYears' => CfmStudyYear::orderByDesc('year')->get(),
            'specialTopics' => CfmSpecialTopic::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, CfmWeek $week): RedirectResponse
    {
        $validated = $request->validate([
            'study_year_id' => 'required|exists:cfm_study_years,id',
            'week_number' => 'required|integer|min:1|max:53',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_special_topic' => 'boolean',
            'special_topic_ids' => 'nullable|array',
            'special_topic_ids.*' => 'exists:cfm_special_topics,id',
        ]);

        $week->update([
            'study_year_id' => $validated['study_year_id'],
            'week_number' => $validated['week_number'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title'] . '-' . $validated['week_number']),
            'description' => $validated['description'] ?? null,
            'is_special_topic' => $validated['is_special_topic'] ?? false,
        ]);

        $week->specialTopics()->sync($validated['special_topic_ids'] ?? []);

        return back()->with('success', 'Week updated successfully.');
    }

    public function destroy(CfmWeek $week): RedirectResponse
    {
        $week->delete();

        return redirect()->route('admin.cfm.weeks.index')
            ->with('success', 'Week deleted successfully.');
    }
}
