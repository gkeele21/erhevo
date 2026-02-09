<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CfmContentType;
use App\Http\Controllers\Controller;
use App\Models\CfmPublisher;
use App\Models\CfmPublisherContent;
use App\Models\CfmStudyYear;
use App\Models\CfmWeek;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminCfmPublisherContentController extends Controller
{
    public function index(Request $request)
    {
        $query = CfmPublisherContent::with(['publisher', 'cfmWeek.studyYear']);

        if ($request->filled('publisher_id')) {
            $query->where('publisher_id', $request->publisher_id);
        }

        if ($request->filled('content_type')) {
            $query->where('content_type', $request->content_type);
        }

        if ($request->filled('study_year_id')) {
            $query->whereHas('cfmWeek', function ($q) use ($request) {
                $q->where('study_year_id', $request->study_year_id);
            });
        }

        $content = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return Inertia::render('Admin/Cfm/PublisherContent/Index', [
            'content' => $content,
            'publishers' => CfmPublisher::orderBy('name')->get(['id', 'name']),
            'studyYears' => CfmStudyYear::orderByDesc('year')->get(['id', 'year', 'title']),
            'contentTypes' => collect(CfmContentType::cases())->map(fn ($type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ]),
            'filters' => $request->only(['publisher_id', 'content_type', 'study_year_id']),
        ]);
    }

    public function create(Request $request)
    {
        $publishers = CfmPublisher::active()->orderBy('name')->get(['id', 'name']);
        $studyYears = CfmStudyYear::with('weeks:id,study_year_id,week_number,title')
            ->orderByDesc('year')
            ->get(['id', 'year', 'title']);
        $contentTypes = collect(CfmContentType::cases())->map(fn ($type) => [
            'value' => $type->value,
            'label' => $type->label(),
            'description' => $type->description(),
        ]);

        return Inertia::render('Admin/Cfm/PublisherContent/Create', [
            'publishers' => $publishers,
            'studyYears' => $studyYears,
            'contentTypes' => $contentTypes,
            'selectedPublisherId' => $request->publisher_id,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'publisher_id' => 'required|exists:cfm_publishers,id',
            'cfm_week_id' => 'required|exists:cfm_weeks,id',
            'title' => 'required|string|max:255',
            'content_type' => 'required|string|in:' . implode(',', array_column(CfmContentType::cases(), 'value')),
            'external_url' => 'required|url|max:500',
            'description' => 'nullable|string',
            'thumbnail_url' => 'nullable|url|max:500',
            'duration_seconds' => 'nullable|integer|min:0',
            'is_featured' => 'boolean',
        ]);

        CfmPublisherContent::create($validated);

        $redirectTo = $request->input('redirect_to', 'index');

        if ($redirectTo === 'publisher') {
            return redirect()->route('admin.cfm.publishers.show', $validated['publisher_id'])
                ->with('success', 'Content created successfully.');
        }

        return redirect()->route('admin.cfm.publisher-content.index')
            ->with('success', 'Content created successfully.');
    }

    public function edit(CfmPublisherContent $publisher_content)
    {
        $publisher_content->load(['publisher', 'cfmWeek']);

        $publishers = CfmPublisher::orderBy('name')->get(['id', 'name']);
        $studyYears = CfmStudyYear::with('weeks:id,study_year_id,week_number,title')
            ->orderByDesc('year')
            ->get(['id', 'year', 'title']);
        $contentTypes = collect(CfmContentType::cases())->map(fn ($type) => [
            'value' => $type->value,
            'label' => $type->label(),
            'description' => $type->description(),
        ]);

        return Inertia::render('Admin/Cfm/PublisherContent/Edit', [
            'publisherContent' => $publisher_content,
            'publishers' => $publishers,
            'studyYears' => $studyYears,
            'contentTypes' => $contentTypes,
        ]);
    }

    public function update(Request $request, CfmPublisherContent $publisher_content)
    {
        $validated = $request->validate([
            'publisher_id' => 'required|exists:cfm_publishers,id',
            'cfm_week_id' => 'required|exists:cfm_weeks,id',
            'title' => 'required|string|max:255',
            'content_type' => 'required|string|in:' . implode(',', array_column(CfmContentType::cases(), 'value')),
            'external_url' => 'required|url|max:500',
            'description' => 'nullable|string',
            'thumbnail_url' => 'nullable|url|max:500',
            'duration_seconds' => 'nullable|integer|min:0',
            'is_featured' => 'boolean',
        ]);

        $publisher_content->update($validated);

        return redirect()->route('admin.cfm.publishers.show', $publisher_content->publisher_id)
            ->with('success', 'Content updated successfully.');
    }

    public function destroy(CfmPublisherContent $publisher_content)
    {
        $publisherId = $publisher_content->publisher_id;
        $publisher_content->delete();

        return redirect()->route('admin.cfm.publishers.show', $publisherId)
            ->with('success', 'Content deleted successfully.');
    }

    public function toggleFeatured(CfmPublisherContent $publisher_content)
    {
        $publisher_content->update(['is_featured' => !$publisher_content->is_featured]);

        return back()->with('success', $publisher_content->is_featured ? 'Content featured.' : 'Content unfeatured.');
    }
}
