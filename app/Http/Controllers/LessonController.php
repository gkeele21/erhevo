<?php

namespace App\Http\Controllers;

use App\Enums\LessonItemType;
use App\Enums\Visibility;
use App\Models\CfmWeek;
use App\Models\Lesson;
use App\Models\Talk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LessonController extends Controller
{
    public function index(Request $request): Response
    {
        $lessons = Lesson::with(['user', 'cfmWeek'])
            ->withCount('items')
            ->visibleTo($request->user())
            ->when($request->search, fn ($q, $search) => $q->where('title', 'like', "%{$search}%"))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Lessons/Index', [
            'lessons' => $lessons,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Lessons/Create', [
            'itemTypes' => $this->itemTypeOptions(),
            'visibilityOptions' => $this->visibilityOptions(),
            'cfmWeeks' => $this->getCfmWeeksForSelect(),
            'currentCfmWeek' => CfmWeek::current()->with('studyYear')->first(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateLesson($request);

        $lesson = new Lesson($validated);
        $lesson->user_id = $request->user()->id;

        if ($validated['publish'] ?? false) {
            $lesson->published_at = now();
        }

        $lesson->save();
        $lesson->syncItems($validated['items'] ?? []);

        return redirect()->route('lessons.show', $lesson)
            ->with('success', 'Lesson created successfully.');
    }

    public function show(Lesson $lesson): Response
    {
        Gate::authorize('view', $lesson);

        $lesson->load(['user', 'cfmWeek.studyYear', 'items']);

        return Inertia::render('Lessons/Show', [
            'lesson' => $lesson,
            'canEdit' => $lesson->user_id === auth()->id(),
        ]);
    }

    public function teach(Lesson $lesson): Response
    {
        Gate::authorize('view', $lesson);

        $lesson->load(['cfmWeek.studyYear', 'items']);

        return Inertia::render('Lessons/Teach', [
            'lesson' => $lesson,
        ]);
    }

    public function edit(Request $request, Lesson $lesson): Response
    {
        Gate::authorize('update', $lesson);

        $lesson->load(['cfmWeek', 'items']);

        return Inertia::render('Lessons/Edit', [
            'lesson' => $lesson,
            'itemTypes' => $this->itemTypeOptions(),
            'visibilityOptions' => $this->visibilityOptions(),
            'cfmWeeks' => $this->getCfmWeeksForSelect(),
            'currentCfmWeek' => CfmWeek::current()->with('studyYear')->first(),
        ]);
    }

    public function update(Request $request, Lesson $lesson)
    {
        Gate::authorize('update', $lesson);

        $validated = $this->validateLesson($request);

        $lesson->fill($validated);

        if (($validated['publish'] ?? false) && ! $lesson->published_at) {
            $lesson->published_at = now();
        }

        $lesson->save();
        $lesson->syncItems($validated['items'] ?? []);

        return redirect()->route('lessons.show', $lesson)
            ->with('success', 'Lesson updated successfully.');
    }

    public function destroy(Lesson $lesson)
    {
        Gate::authorize('delete', $lesson);

        $lesson->delete();

        return redirect()->route('lessons.index')
            ->with('success', 'Lesson deleted successfully.');
    }

    /**
     * Search the talks library for the lesson Talk-block picker.
     */
    public function searchTalks(Request $request)
    {
        abort_unless($request->user()->show_lds_content, 403);

        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $talks = Talk::where(function ($q) use ($query) {
            $q->where('title', 'like', "%{$query}%")
                ->orWhere('speaker_name', 'like', "%{$query}%");
        })
            ->ordered()
            ->limit(10)
            ->get()
            ->map(fn (Talk $talk) => [
                'talk_id' => $talk->id,
                'title' => $talk->title,
                'speaker' => $talk->speaker_display_name,
                'url' => $talk->url,
            ]);

        return response()->json($talks);
    }

    protected function validateLesson(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'cfm_week_id' => 'nullable|exists:cfm_weeks,id',
            'visibility' => 'required|in:public,private,friends',
            'publish' => 'boolean',
            'items' => 'nullable|array',
            'items.*.type' => 'required|in:scripture,talk,video,text,question',
            'items.*.content' => 'nullable|string',
            'items.*.config' => 'nullable|array',
        ]);
    }

    protected function itemTypeOptions(): array
    {
        return collect(LessonItemType::cases())->map(fn ($t) => [
            'value' => $t->value,
            'label' => $t->label(),
            'description' => $t->description(),
            'icon' => $t->icon(),
        ])->toArray();
    }

    protected function visibilityOptions(): array
    {
        return collect(Visibility::cases())->map(fn ($v) => [
            'value' => $v->value,
            'label' => $v->label(),
            'description' => $v->description(),
        ])->toArray();
    }

    /**
     * Get CFM weeks for the select dropdown, grouped by study year.
     */
    protected function getCfmWeeksForSelect(): array
    {
        return CfmWeek::with('studyYear')
            ->whereHas('studyYear', fn ($q) => $q->where('year', '>=', now()->year - 2))
            ->orderByDesc('start_date')
            ->get()
            ->toArray();
    }
}
