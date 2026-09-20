<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonItem;
use App\Models\Post;
use App\Models\ScriptureBook;
use App\Models\ScriptureChapter;
use App\Models\ScriptureReference;
use App\Models\ScriptureVolume;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Browsing the scriptures, and seeing what has been written about them.
 */
class ScriptureController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Scriptures/Index', [
            'volumes' => $this->booksTree(),
            'recent' => $this->recentlyDiscussedChapters($request),
        ]);
    }

    public function show(Request $request, ScriptureBook $book, int $chapter): Response
    {
        $chapter = ScriptureChapter::where('book_id', $book->id)
            ->where('chapter_number', $chapter)
            ->firstOrFail();

        $chapter->setRelation('book', $book);

        $references = $this->visibleReferencesFor($request, $chapter);

        return Inertia::render('Scriptures/Show', [
            'book' => [
                'id' => $book->id,
                'name' => $book->name,
                'slug' => $book->slug,
                'chapter_count' => $book->chapter_count,
            ],
            'chapter' => [
                'id' => $chapter->id,
                'number' => $chapter->chapter_number,
                'reference' => $chapter->full_reference,
            ],
            'verses' => $chapter->verses->map(fn ($verse) => [
                'number' => $verse->verse_number,
                'text' => $verse->text,
                // Which entries touch this verse, so the page can show a count
                // in the margin and filter on click.
                'entry_ids' => $references
                    ->filter(fn ($ref) => $ref->coversVerse($chapter, $verse->verse_number))
                    ->pluck('entry_id')
                    ->unique()
                    ->values(),
            ]),
            'entries' => $this->entriesFor($references),
            'prevChapter' => $chapter->chapter_number > 1 ? $chapter->chapter_number - 1 : null,
            'nextChapter' => $chapter->chapter_number < $book->chapter_count ? $chapter->chapter_number + 1 : null,
        ]);
    }

    /**
     * References covering this chapter that the viewer is allowed to see.
     *
     * Visibility lives on the post or on the lesson that owns the block, so
     * the two sides are resolved separately and then stitched back together.
     */
    private function visibleReferencesFor(Request $request, ScriptureChapter $chapter)
    {
        $user = $request->user();

        $references = ScriptureReference::coveringChapter($chapter)
            ->with(['startChapter.book', 'endChapter'])
            ->get();

        $postIds = $references->where('referenceable_type', Post::class)->pluck('referenceable_id');
        $itemIds = $references->where('referenceable_type', LessonItem::class)->pluck('referenceable_id');

        $posts = Post::whereIn('id', $postIds)
            ->visibleTo($user)
            ->published()
            ->with(['user', 'author'])
            ->get()
            ->keyBy('id');

        $items = LessonItem::whereIn('id', $itemIds)
            ->whereHas('lesson', fn ($q) => $q->visibleTo($user)->published())
            ->with(['lesson.user'])
            ->get()
            ->keyBy('id');

        return $references
            ->map(function (ScriptureReference $ref) use ($posts, $items) {
                $subject = $ref->referenceable_type === Post::class
                    ? $posts->get($ref->referenceable_id)
                    : $items->get($ref->referenceable_id);

                if (! $subject) {
                    return null;
                }

                // One entry per underlying post/lesson, so a lesson quoting a
                // passage three times shows up once.
                $ref->subject = $subject;
                $ref->entry_id = $ref->referenceable_type === Post::class
                    ? 'post-' . $subject->id
                    : 'lesson-' . $subject->lesson_id;

                return $ref;
            })
            ->filter()
            ->values();
    }

    /**
     * Collapse references into the things a reader actually clicks through to.
     */
    private function entriesFor($references): array
    {
        return $references
            ->groupBy('entry_id')
            ->map(function ($group) {
                $ref = $group->first();
                $subject = $ref->subject;
                $passages = $group->map->display_reference->unique()->values();

                if ($ref->referenceable_type === Post::class) {
                    return [
                        'id' => $ref->entry_id,
                        'kind' => 'post',
                        'title' => $subject->title,
                        'excerpt' => $subject->excerpt,
                        'by' => $subject->creator_name,
                        'url' => route('posts.show', $subject),
                        'passages' => $passages,
                    ];
                }

                $lesson = $subject->lesson;

                return [
                    'id' => $ref->entry_id,
                    'kind' => $lesson->kind === 'talk' ? 'talk' : 'lesson',
                    'title' => $lesson->title,
                    'excerpt' => $lesson->description,
                    'by' => $lesson->user?->name,
                    'url' => route('lessons.show', $lesson),
                    'passages' => $passages,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Chapters with the most recent activity, as a way in to the browse page.
     */
    private function recentlyDiscussedChapters(Request $request): array
    {
        $user = $request->user();

        $visiblePostIds = Post::visibleTo($user)->published()->pluck('id');

        return ScriptureReference::query()
            ->where('referenceable_type', Post::class)
            ->whereIn('referenceable_id', $visiblePostIds)
            ->with('startChapter.book')
            ->latest('created_at')
            ->get()
            ->unique('start_chapter_id')
            ->take(8)
            ->map(fn ($ref) => [
                'reference' => $ref->startChapter->full_reference,
                'url' => route('scriptures.show', [
                    'book' => $ref->startChapter->book->slug,
                    'chapter' => $ref->startChapter->chapter_number,
                ]),
            ])
            ->values()
            ->all();
    }

    /**
     * Volumes → books, with chapter counts for the browse page.
     */
    private function booksTree(): array
    {
        return ScriptureVolume::query()
            ->orderBy('sort_order')
            ->with(['books' => fn ($q) => $q->orderBy('sort_order')])
            ->get()
            ->map(fn ($volume) => [
                'name' => $volume->name,
                'books' => $volume->books->map(fn ($book) => [
                    'name' => $book->name,
                    'slug' => $book->slug,
                    'chapter_count' => $book->chapter_count,
                ])->values(),
            ])->values()->toArray();
    }
}
