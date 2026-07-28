<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\ChurchCalling;
use App\Models\GeneralConference;
use App\Models\ScriptureVolume;
use App\Models\StudyPlan;
use App\Models\StudyPlanItem;
use App\Services\StudyPlanScheduler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StudyPlanController extends Controller
{
    public function index(Request $request): Response
    {
        $plans = StudyPlan::where('user_id', $request->user()->id)
            ->withCount([
                'items',
                'items as completed_items_count' => fn ($q) => $q->whereNotNull('completed_at'),
            ])
            ->latest()
            ->get()
            ->each->append('criteria_summary');

        return Inertia::render('StudyPlans/Index', [
            'plans' => $plans,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('StudyPlans/Create', $this->pickerData());
    }

    public function store(Request $request, StudyPlanScheduler $scheduler)
    {
        $validated = $this->validatePlan($request);

        $plan = new StudyPlan($this->planAttributes($validated));
        $plan->user_id = $request->user()->id;
        $plan->save();

        if ($scheduler->generate($plan) === 0) {
            $plan->delete();

            return back()->withErrors([
                'criteria' => 'Nothing matched those criteria — try widening the author, calling, or date range.',
            ])->withInput();
        }

        return redirect()->route('study-plans.show', $plan)
            ->with('success', 'Study plan created.');
    }

    public function edit(StudyPlan $studyPlan): Response
    {
        Gate::authorize('update', $studyPlan);

        return Inertia::render('StudyPlans/Edit', [
            'plan' => $studyPlan,
            ...$this->pickerData(),
        ]);
    }

    public function update(Request $request, StudyPlan $studyPlan, StudyPlanScheduler $scheduler)
    {
        Gate::authorize('update', $studyPlan);

        $validated = $this->validatePlan($request);

        try {
            DB::transaction(function () use ($studyPlan, $validated, $scheduler) {
                $studyPlan->update($this->planAttributes($validated));

                if ($scheduler->generate($studyPlan) === 0) {
                    throw new \RuntimeException('study-plan-empty');
                }
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() !== 'study-plan-empty') {
                throw $e;
            }

            return back()->withErrors([
                'criteria' => 'Nothing matched those criteria — try widening the author, calling, or date range.',
            ])->withInput();
        }

        return redirect()->route('study-plans.show', $studyPlan)
            ->with('success', 'Study plan updated.');
    }

    public function show(StudyPlan $studyPlan): Response
    {
        Gate::authorize('view', $studyPlan);

        $studyPlan->append('criteria_summary');
        $studyPlan->load([
            'items.chapter.book:id,name',
            'items.talk:id,title,slug,speaker_name,speaker_title,summary,talk_date,url,church_calling_id',
            'items.talk.calling:id,prefix,name,church_organization_id',
            'items.talk.calling.organization:id,name',
            'items.talk.tags:id,name,slug',
        ]);

        // "President Russell M. Nelson" (calling prefix) or "Name, title",
        // plus the calling held when the talk was given ("The Quorum of the
        // Twelve Apostles — Apostle").
        $studyPlan->items->each(function ($item) {
            $item->talk?->append('speaker_display_name');
            $item->talk?->calling?->append('display_label');
        });

        return Inertia::render('StudyPlans/Show', [
            'plan' => $studyPlan,
        ]);
    }

    public function toggleItem(Request $request, StudyPlan $studyPlan, StudyPlanItem $item)
    {
        Gate::authorize('update', $studyPlan);
        abort_unless($item->study_plan_id === $studyPlan->id, 404);

        $item->update([
            'completed_at' => $item->completed_at ? null : now(),
        ]);

        return back();
    }

    public function destroy(StudyPlan $studyPlan)
    {
        Gate::authorize('delete', $studyPlan);

        $studyPlan->delete();

        return redirect()->route('study-plans.index')
            ->with('success', 'Study plan deleted.');
    }

    protected function validatePlan(Request $request): array
    {
        // The form submits both criteria sections' fields, so talk fields are
        // only required for talk plans (and vice versa) — never across types.
        $talksByAuthor = $request->input('type') === 'talks' && $request->input('mode') === 'author';
        $talksByCalling = $request->input('type') === 'talks' && $request->input('mode') === 'calling';
        $talksByConference = $request->input('type') === 'talks' && $request->input('mode') === 'conference';

        return $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:scripture,talks',
            'start_date' => 'nullable|date|required_with:end_date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'frequency' => 'nullable|in:daily,weekdays,weekly|required_with:start_date',
            // Scripture criteria
            'volume_id' => 'required_if:type,scripture|nullable|exists:scripture_volumes,id',
            'book_ids' => 'nullable|array',
            'book_ids.*' => 'integer|exists:scripture_books,id',
            // Talk criteria
            'mode' => 'required_if:type,talks|nullable|in:author,calling,conference',
            'author_id' => [Rule::requiredIf($talksByAuthor), 'nullable', 'exists:authors,id'],
            'author_calling_id' => 'nullable|exists:author_callings,id',
            'church_calling_id' => [Rule::requiredIf($talksByCalling), 'nullable', 'exists:church_callings,id'],
            'years_back' => 'nullable|integer|min:1|max:100',
            'general_conference_id' => [Rule::requiredIf($talksByConference), 'nullable', 'exists:general_conferences,id'],
        ]);
    }

    /** Model attributes (including the type-specific config) from validated input. */
    protected function planAttributes(array $validated): array
    {
        $config = $validated['type'] === 'scripture'
            ? [
                'volume_id' => $validated['volume_id'],
                'book_ids' => $validated['book_ids'] ?? null,
            ]
            : match ($validated['mode']) {
                'author' => [
                    'mode' => 'author',
                    'author_id' => $validated['author_id'],
                    'author_calling_id' => $validated['author_calling_id'] ?? null,
                ],
                'calling' => [
                    'mode' => 'calling',
                    'church_calling_id' => $validated['church_calling_id'],
                    'years_back' => $validated['years_back'] ?? null,
                ],
                'conference' => [
                    'mode' => 'conference',
                    'general_conference_id' => $validated['general_conference_id'],
                ],
            };

        return [
            'name' => $validated['name'],
            'type' => $validated['type'],
            'config' => $config,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'frequency' => $validated['frequency'] ?? null,
        ];
    }

    /** Shared props for the create/edit form pickers. */
    protected function pickerData(): array
    {
        return [
            'volumes' => ScriptureVolume::with('books:id,volume_id,name,sort_order')
                ->orderBy('sort_order')
                ->get(['id', 'name', 'sort_order']),
            'authors' => $this->authorsWithCallings(),
            'churchCallings' => ChurchCalling::with('organization')
                ->orderBy('church_organization_id')
                ->orderBy('name')
                ->get()
                ->map(fn (ChurchCalling $c) => ['id' => $c->id, 'label' => $c->display_label])
                ->values(),
            'conferences' => GeneralConference::whereHas('talks')
                ->orderByDesc('start_date')
                ->get(['id', 'name']),
        ];
    }

    /**
     * Authors for the picker, with their calling history so a plan can be
     * limited to one calling's window (e.g. talks given only as President).
     */
    protected function authorsWithCallings()
    {
        return Author::whereHas('talks')
            ->with(['callings.calling.organization'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn (Author $author) => [
                'id' => $author->id,
                'name' => $author->full_name,
                'callings' => $author->callings->map(fn ($ac) => [
                    'id' => $ac->id,
                    'label' => $ac->calling?->display_label ?? 'Calling',
                    'start_date' => $ac->start_date?->toDateString(),
                    'end_date' => $ac->end_date?->toDateString(),
                ])->values(),
            ])
            ->values();
    }
}
