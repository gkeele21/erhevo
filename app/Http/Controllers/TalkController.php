<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\GeneralConference;
use App\Models\GeneralConferenceSessionType;
use App\Models\Source;
use App\Models\Tag;
use App\Models\Talk;
use App\Models\TalkRead;
use App\Models\User;
use App\Services\TalkFilterOptions;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TalkController extends Controller
{
    public function index(Request $request): Response
    {
        // Guests may browse; members who turned LDS content off may not.
        abort_if($request->user() && ! $request->user()->show_lds_content, 403);

        // Checks off the dashboard's Getting Started step.
        if (($user = $request->user()) && ! $user->getSetting('visited_library')) {
            $user->setSetting('visited_library', true)->save();
        }

        $isGeneralConference = $request->source === 'general-conference';
        $talkOptions = app(TalkFilterOptions::class);
        $authorId = (int) $request->input('author_id') ?: null;

        $talks = $this->filteredTalks($request, $isGeneralConference)
            ->with($this->listRelations($user))
            // Sessions sort as whole blocks (display_order restarts per
            // session, so same-day sessions need the session's own ordering);
            // talks always read in speaking order within their session.
            ->leftJoin('general_conference_sessions as gc_sessions', 'talks.general_conference_session_id', '=', 'gc_sessions.id')
            ->select('talks.*')
            ->tap(fn ($q) => match ($request->input('sort', 'oldest')) {
                'newest' => $q->orderByRaw('COALESCE(gc_sessions.session_date, talks.talk_date) DESC')
                    ->orderByRaw('gc_sessions.display_order DESC')
                    ->orderBy('talks.display_order'),
                'title' => $q->orderBy('talks.title'),
                'speaker' => $q->orderBy('talks.speaker_name')
                    ->orderByRaw('COALESCE(gc_sessions.session_date, talks.talk_date) DESC'),
                // Unrated talks sort last rather than first (NULL would lead).
                'rating' => $q->orderByRaw('talks.average_rating IS NULL')
                    ->orderBy('talks.average_rating', 'desc')
                    ->orderBy('talks.ratings_count', 'desc')
                    ->orderBy('talks.title'),
                default => $q->orderByRaw('COALESCE(gc_sessions.session_date, talks.talk_date) ASC')
                    ->orderBy('gc_sessions.display_order')
                    ->orderBy('talks.display_order'),
            })
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Talk $talk) => $this->transformTalk($talk, $user));

        return Inertia::render('Talks/Index', [
            'talks' => $talks,
            // A different talk each request, drawn from the same filtered set —
            // a way in for people who don't know what they're looking for.
            'randomTalk' => fn () => $this->randomTalk($request, $isGeneralConference, $user),
            // Only sources that actually have talks are worth filtering by.
            'sources' => Source::active()->whereHas('talks')->orderBy('name')->get(['id', 'name', 'slug']),
            'conferenceFilters' => $this->conferenceFilterOptions($request, $isGeneralConference),
            'sessionTypes' => GeneralConferenceSessionType::orderBy('display_order')->get(['id', 'name']),
            // Callings and conferences are small lists, so they ship with the
            // page. Authors number in the thousands and are searched instead.
            'churchCallings' => $talkOptions->churchCallings(),
            'conferences' => $talkOptions->conferences(),
            'selectedAuthor' => $this->selectedAuthor($authorId),
            'authorCallings' => $authorId ? $talkOptions->callingsForAuthor($authorId) : [],
            'filters' => [
                ...$request->only(['source', 'search', 'year', 'month', 'session', 'session_type', 'tag', 'sort']),
                'author_id' => $authorId,
                'author_calling_ids' => $this->authorCallingIds($request),
                'church_calling_id' => (int) $request->input('church_calling_id') ?: null,
                'general_conference_id' => (int) $request->input('general_conference_id') ?: null,
                'years_back' => $this->yearsBack($request),
                'min_rating' => $this->minRating($request),
                'favorites' => $request->boolean('favorites'),
            ],
            'activeTag' => $request->tag
                ? Tag::where('slug', $request->tag)->first(['id', 'name', 'slug'])
                : null,
            // Rating, favoriting and read dates are all per-user.
            'canEngage' => (bool) $user,
        ]);
    }

    /**
     * Type-ahead for the library's author filter. Public, because guests can
     * browse the library, and scoped to authors who actually have talks.
     */
    public function searchAuthors(Request $request)
    {
        abort_if($request->user() && ! $request->user()->show_lds_content, 403);

        $term = trim((string) $request->input('q', ''));

        if (strlen($term) < 2) {
            return response()->json([]);
        }

        return response()->json(app(TalkFilterOptions::class)->searchAuthors($term));
    }

    /**
     * The library scoped to the user's favorites. Its own URL so it can be
     * linked and bookmarked; everything else about the page is unchanged, and
     * the user can layer the normal filters on top.
     */
    public function favorites(Request $request): Response
    {
        $request->merge(['favorites' => 1]);

        return $this->index($request);
    }

    /**
     * The talk query with every active filter applied but no ordering, so the
     * paginated list and the random pick draw from exactly the same set.
     */
    private function filteredTalks(Request $request, bool $isGeneralConference): Builder
    {
        $user = $request->user();

        return Talk::query()
            ->when($request->source, fn ($q, $source) => $q->bySource($source))
            ->when($request->tag, fn ($q, $tag) => $q->whereHas('tags', fn ($q2) => $q2->where('slug', $tag)))
            ->when($isGeneralConference && $request->year, fn ($q) => $q->whereHas(
                'conferenceSession.conference',
                fn ($q2) => $q2->where('year', $request->year)
            ))
            ->when($isGeneralConference && $request->month, fn ($q) => $q->whereHas(
                'conferenceSession.conference',
                fn ($q2) => $q2->where('month', $request->month)
            ))
            ->when($isGeneralConference && $request->session, fn ($q) => $q->where(
                'general_conference_session_id',
                $request->session
            ))
            ->when($isGeneralConference && $request->session_type, fn ($q) => $q->whereHas(
                'conferenceSession',
                fn ($q2) => $q2->where('session_type_id', $request->session_type)
            ))
            ->when($request->search, fn ($q, $search) => $q->where(function ($q2) use ($search) {
                $q2->where('title', 'like', "%{$search}%")
                    ->orWhere('speaker_name', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%");
            }))
            // Author, calling and conference narrowing, matching the study plan
            // builder's criteria so a search and a plan select the same talks.
            ->when($request->author_id, fn ($q, $authorId) => $q->where('talks.author_id', $authorId))
            ->when(
                $request->author_id && $this->authorCallingIds($request),
                fn ($q) => $q->withinCallingWindows($this->authorCallingIds($request))
            )
            ->when($request->church_calling_id, fn ($q, $callingId) => $q->where('talks.church_calling_id', $callingId))
            ->when($request->general_conference_id, fn ($q, $conferenceId) => $q->whereHas(
                'conferenceSession',
                fn ($q2) => $q2->where('general_conference_id', $conferenceId)
            ))
            ->when($this->yearsBack($request), fn ($q, $years) => $q->whereDate(
                'talks.talk_date',
                '>=',
                now()->subYears($years)->toDateString()
            ))
            ->when($this->minRating($request), fn ($q, $rating) => $q->minRating($rating))
            // Favorites are personal, so this filter is a no-op for guests.
            ->when($user && $request->boolean('favorites'), fn ($q) => $q->favoritedBy($user->id));
    }

    /**
     * One talk drawn at random from the current filter set. Full-scan random
     * ordering is fine at library scale (tens of thousands of rows at most).
     */
    private function randomTalk(Request $request, bool $isGeneralConference, ?User $user): ?array
    {
        $talk = $this->filteredTalks($request, $isGeneralConference)
            ->with($this->listRelations($user))
            ->inRandomOrder()
            ->first();

        return $talk ? $this->transformTalk($talk, $user) : null;
    }

    /**
     * Eager loads for a talk card. The engagement relations are constrained to
     * the current user — a card only ever shows that user's own rating, star
     * and read dates.
     */
    private function listRelations(?User $user): array
    {
        $relations = ['source', 'talkType', 'calling.organization', 'conferenceSession:id,name', 'tags:id,name,slug'];

        if ($user) {
            $relations['ratings'] = fn ($q) => $q->where('user_id', $user->id);
            $relations['favorites'] = fn ($q) => $q->where('user_id', $user->id);
            $relations['reads'] = fn ($q) => $q->where('user_id', $user->id)->orderBy('read_on', 'desc');
        }

        return $relations;
    }

    private function transformTalk(Talk $talk, ?User $user): array
    {
        return [
            'id' => $talk->id,
            'title' => $talk->title,
            'speaker_name' => $talk->speaker_name,
            'speaker_display_name' => $talk->speaker_display_name,
            'calling' => $talk->calling?->display_label,
            'summary' => $talk->summary,
            'talk_date' => $talk->talk_date?->format('F Y'),
            'session' => $talk->conferenceSession?->name,
            'url' => $talk->url,
            'source' => $talk->source?->name,
            'tags' => $talk->tags->map->only(['id', 'name', 'slug']),
            'average_rating' => $talk->average_rating,
            'ratings_count' => $talk->ratings_count,
            'my_rating' => $user ? $talk->ratings->first()?->rating : null,
            'is_favorite' => $user ? $talk->favorites->isNotEmpty() : false,
            'reads' => $user
                ? $talk->reads->map(fn (TalkRead $read) => [
                    'id' => $read->id,
                    'read_on' => $read->read_on->toDateString(),
                    'label' => $read->read_on->format('M j, Y'),
                ])->values()
                : [],
        ];
    }

    /**
     * The author behind an active author_id filter, so the picker can show a
     * name rather than an opaque id after a reload.
     */
    private function selectedAuthor(?int $authorId): ?array
    {
        if (! $authorId) {
            return null;
        }

        $author = Author::find($authorId);

        return $author ? ['id' => $author->id, 'name' => $author->full_name] : null;
    }

    /**
     * The selected calling windows. A bare `author_calling_id` is still honored
     * so links and bookmarks from when this filter was single-select keep working.
     *
     * @return array<int, int>
     */
    private function authorCallingIds(Request $request): array
    {
        $ids = array_merge(
            (array) $request->input('author_calling_ids', []),
            (array) $request->input('author_calling_id', [])
        );

        return collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** The years_back filter, or null when absent or out of the 1–100 range. */
    private function yearsBack(Request $request): ?int
    {
        $years = (int) $request->input('years_back');

        return $years >= 1 && $years <= 100 ? $years : null;
    }

    /** The min_rating filter, or null when absent or out of the 1–5 range. */
    private function minRating(Request $request): ?int
    {
        $rating = (int) $request->input('min_rating');

        return $rating >= 1 && $rating <= 5 ? $rating : null;
    }

    /**
     * Build the cascading General Conference filter options (year → month → session)
     * based on what the user has already selected.
     */
    private function conferenceFilterOptions(Request $request, bool $isGeneralConference): array
    {
        $options = [
            'years' => [],
            'months' => [],
            'sessions' => [],
        ];

        if (! $isGeneralConference) {
            return $options;
        }

        $options['years'] = GeneralConference::query()
            ->select('year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        if ($request->year) {
            $options['months'] = GeneralConference::where('year', $request->year)
                ->orderBy('month') // 'april' sorts before 'october'
                ->pluck('month')
                ->map(fn ($month) => ['value' => $month, 'label' => ucfirst($month)])
                ->values();

            if ($request->month) {
                $conference = GeneralConference::where('year', $request->year)
                    ->where('month', $request->month)
                    ->first();

                if ($conference) {
                    $options['sessions'] = $conference->sessions()
                        ->orderBy('display_order')
                        ->get(['id', 'name']);
                }
            }
        }

        return $options;
    }
}
