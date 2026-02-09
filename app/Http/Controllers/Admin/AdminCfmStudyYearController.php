<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CfmStudyYear;
use App\Models\ScriptureVolume;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminCfmStudyYearController extends Controller
{
    public function index(): Response
    {
        $studyYears = CfmStudyYear::withCount('weeks')
            ->with('volumes')
            ->orderByDesc('year')
            ->paginate(10);

        return Inertia::render('Admin/Cfm/StudyYears/Index', [
            'studyYears' => $studyYears,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Cfm/StudyYears/Create', [
            'volumes' => ScriptureVolume::orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100|unique:cfm_study_years,year',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'volume_ids' => 'nullable|array',
            'volume_ids.*' => 'exists:scripture_volumes,id',
        ]);

        $studyYear = CfmStudyYear::create([
            'year' => $validated['year'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
        ]);

        if (!empty($validated['volume_ids'])) {
            $studyYear->volumes()->attach($validated['volume_ids']);
        }

        return redirect()->route('admin.cfm.study-years.index')
            ->with('success', 'Study year created successfully.');
    }

    public function show(CfmStudyYear $studyYear): Response
    {
        $studyYear->load(['weeks' => fn ($q) => $q->orderBy('week_number'), 'volumes']);

        return Inertia::render('Admin/Cfm/StudyYears/Show', [
            'studyYear' => $studyYear,
        ]);
    }

    public function edit(CfmStudyYear $studyYear): Response
    {
        $studyYear->load('volumes');

        return Inertia::render('Admin/Cfm/StudyYears/Edit', [
            'studyYear' => $studyYear,
            'volumes' => ScriptureVolume::orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, CfmStudyYear $studyYear): RedirectResponse
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100|unique:cfm_study_years,year,' . $studyYear->id,
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'volume_ids' => 'nullable|array',
            'volume_ids.*' => 'exists:scripture_volumes,id',
        ]);

        $studyYear->update([
            'year' => $validated['year'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
        ]);

        $studyYear->volumes()->sync($validated['volume_ids'] ?? []);

        return back()->with('success', 'Study year updated successfully.');
    }

    public function destroy(CfmStudyYear $studyYear): RedirectResponse
    {
        $studyYear->delete();

        return redirect()->route('admin.cfm.study-years.index')
            ->with('success', 'Study year deleted successfully.');
    }
}
