<?php

namespace App\Http\Controllers;

use App\Enums\LessonItemType;
use App\Enums\Visibility;
use App\Models\CfmWeek;
use App\Models\Lesson;
use App\Models\ScriptureChapter;
use App\Models\ScriptureVerse;
use App\Models\ScriptureVolume;
use App\Models\Talk;
use App\Services\ScriptureReferenceParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class LessonController extends Controller
{
    /** Upload size caps (KB) for lesson media. */
    private const VIDEO_MAX_KB = 20480; // 20 MB
    private const IMAGE_MAX_KB = 10240; // 10 MB

    public function index(Request $request): Response
    {
        $lessons = Lesson::with(['user', 'cfmWeek'])
            ->withCount(['allItems as items_count' => fn ($q) => $q->where('type', '!=', 'group')])
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
            'scriptureBooks' => $this->scriptureBooksTree(),
            'uploadLimits' => $this->uploadLimits(),
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

        $lesson->load(['user', 'cfmWeek.studyYear', 'items.children']);

        return Inertia::render('Lessons/Show', [
            'lesson' => $lesson,
            'canEdit' => $lesson->user_id === auth()->id(),
        ]);
    }

    public function teach(Lesson $lesson): Response
    {
        Gate::authorize('view', $lesson);

        $lesson->load(['cfmWeek.studyYear', 'items.children']);

        return Inertia::render('Lessons/Teach', [
            'lesson' => $lesson,
        ]);
    }

    public function edit(Request $request, Lesson $lesson): Response
    {
        Gate::authorize('update', $lesson);

        $lesson->load(['cfmWeek', 'items.children']);

        return Inertia::render('Lessons/Edit', [
            'lesson' => $lesson,
            'itemTypes' => $this->itemTypeOptions(),
            'visibilityOptions' => $this->visibilityOptions(),
            'cfmWeeks' => $this->getCfmWeeksForSelect(),
            'currentCfmWeek' => CfmWeek::current()->with('studyYear')->first(),
            'scriptureBooks' => $this->scriptureBooksTree(),
            'uploadLimits' => $this->uploadLimits(),
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

    /**
     * Upload a local video file for a lesson Video block.
     * Returns a public URL + the stored path.
     */
    public function uploadVideo(Request $request)
    {
        $request->validate([
            // Server upload limit is also governed by php.ini (upload_max_filesize / post_max_size).
            'video' => 'required|file|mimetypes:video/mp4,video/webm,video/ogg,video/quicktime|max:' . self::VIDEO_MAX_KB,
        ]);

        $file = $request->file('video');
        // Store under a per-user folder so deletes can be scoped to the owner.
        $path = $file->store('lesson-videos/' . $request->user()->id, 'public');

        return response()->json([
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'filename' => $file->getClientOriginalName(),
        ]);
    }

    /**
     * Delete a previously uploaded video file. Scoped to the current user's
     * upload folder so a user can only remove their own files.
     */
    public function deleteVideo(Request $request)
    {
        $validated = $request->validate([
            'path' => 'required|string',
        ]);

        $prefix = 'lesson-videos/' . $request->user()->id . '/';
        if (! str_starts_with($validated['path'], $prefix)) {
            abort(403);
        }

        Storage::disk('public')->delete($validated['path']);

        return response()->json(['success' => true]);
    }

    /**
     * Upload a local image file for a lesson Image block.
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,gif,webp|max:' . self::IMAGE_MAX_KB,
        ]);

        $file = $request->file('image');
        $path = $file->store('lesson-images/' . $request->user()->id, 'public');

        return response()->json([
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'filename' => $file->getClientOriginalName(),
        ]);
    }

    /**
     * Delete a previously uploaded image file, scoped to the current user.
     */
    public function deleteImage(Request $request)
    {
        $validated = $request->validate([
            'path' => 'required|string',
        ]);

        $prefix = 'lesson-images/' . $request->user()->id . '/';
        if (! str_starts_with($validated['path'], $prefix)) {
            abort(403);
        }

        Storage::disk('public')->delete($validated['path']);

        return response()->json(['success' => true]);
    }

    /**
     * Return the verse text + display reference for a (possibly cross-chapter)
     * range, used by the lesson Scripture-block picker to auto-fill the passage.
     */
    public function scriptureText(Request $request, ScriptureReferenceParser $parser)
    {
        $validated = $request->validate([
            'start_chapter_id' => 'required|exists:scripture_chapters,id',
            'start_verse' => 'nullable|integer|min:1',
            'end_chapter_id' => 'nullable|exists:scripture_chapters,id',
            'end_verse' => 'nullable|integer|min:1',
        ]);

        $startChapter = ScriptureChapter::with('book')->findOrFail($validated['start_chapter_id']);
        $endChapter = ! empty($validated['end_chapter_id'])
            ? ScriptureChapter::with('book')->findOrFail($validated['end_chapter_id'])
            : $startChapter;

        // A range must stay within one book and run forward.
        if ($endChapter->book_id !== $startChapter->book_id
            || $endChapter->chapter_number < $startChapter->chapter_number) {
            return response()->json(['message' => 'The end chapter must be in the same book and after the start chapter.'], 422);
        }

        $startVerse = $validated['start_verse'] ?? null;
        $endVerse = $validated['end_verse'] ?? null;

        $chapters = ScriptureChapter::where('book_id', $startChapter->book_id)
            ->whereBetween('chapter_number', [$startChapter->chapter_number, $endChapter->chapter_number])
            ->orderBy('chapter_number')
            ->get();

        $multiChapter = $chapters->count() > 1;
        $lines = [];

        foreach ($chapters as $chapter) {
            $verses = ScriptureVerse::where('chapter_id', $chapter->id)
                ->when($chapter->id === $startChapter->id && $startVerse,
                    fn ($q) => $q->where('verse_number', '>=', $startVerse))
                ->when($chapter->id === $endChapter->id && $endVerse,
                    fn ($q) => $q->where('verse_number', '<=', $endVerse))
                ->orderBy('verse_number')
                ->get();

            // Label each chapter when the passage spans more than one.
            if ($multiChapter) {
                $lines[] = "{$startChapter->book->name} {$chapter->chapter_number}";
            }

            foreach ($verses as $v) {
                $lines[] = trim("{$v->verse_number} " . ($v->text ?? ''));
            }
        }

        return response()->json([
            'reference' => $parser->format($startChapter, $startVerse, $endChapter, $endVerse),
            'text' => implode("\n", $lines),
        ]);
    }

    /**
     * Effective max upload size (in MB) for each media type — the smaller of
     * our validation cap and the server's PHP limits — so the UI shows the
     * real, accurate limit.
     */
    protected function uploadLimits(): array
    {
        $iniMaxKb = min(
            $this->iniBytes(ini_get('upload_max_filesize')),
            $this->iniBytes(ini_get('post_max_size'))
        ) / 1024;

        return [
            'video_mb' => (int) round(min(self::VIDEO_MAX_KB, $iniMaxKb) / 1024),
            'image_mb' => (int) round(min(self::IMAGE_MAX_KB, $iniMaxKb) / 1024),
        ];
    }

    /**
     * Convert a php.ini size shorthand (e.g. "20M", "1G", "512K") to bytes.
     */
    private function iniBytes(?string $value): int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return PHP_INT_MAX;
        }

        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => (int) $value,
        };
    }

    /**
     * Volumes → books → chapters (with verse_count) for the scripture picker.
     */
    protected function scriptureBooksTree(): array
    {
        return ScriptureVolume::query()
            ->orderBy('sort_order')
            ->with(['books' => fn ($q) => $q->orderBy('sort_order')
                ->with(['chapters' => fn ($q2) => $q2->orderBy('chapter_number')])])
            ->get()
            ->map(fn ($volume) => [
                'name' => $volume->name,
                'books' => $volume->books->map(fn ($book) => [
                    'id' => $book->id,
                    'name' => $book->name,
                    'chapters' => $book->chapters->map(fn ($c) => [
                        'id' => $c->id,
                        'number' => $c->chapter_number,
                        'verse_count' => $c->verse_count,
                    ])->values(),
                ])->values(),
            ])->values()->toArray();
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
            'items.*.type' => 'required|in:scripture,talk,video,image,text,question,group',
            'items.*.content' => 'nullable|string',
            'items.*.config' => 'nullable|array',
            // Group children (one level deep; children may not themselves be groups).
            'items.*.children' => 'nullable|array',
            'items.*.children.*.type' => 'required|in:scripture,talk,video,image,text,question',
            'items.*.children.*.content' => 'nullable|string',
            'items.*.children.*.config' => 'nullable|array',
        ]);
    }

    protected function itemTypeOptions(): array
    {
        return collect(LessonItemType::contentCases())->map(fn ($t) => [
            'value' => $t->value,
            'label' => $t->label(),
            'description' => $t->description(),
            'icon' => $t->icon(),
        ])->values()->toArray();
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
