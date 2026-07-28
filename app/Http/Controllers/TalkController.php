<?php

namespace App\Http\Controllers;

use App\Models\GeneralConference;
use App\Models\GeneralConferenceSessionType;
use App\Models\Source;
use App\Models\Tag;
use App\Models\Talk;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TalkController extends Controller
{
    public function index(Request $request): Response
    {
        // Guests may browse; members who turned LDS content off may not.
        abort_if($request->user() && ! $request->user()->show_lds_content, 403);

        $isGeneralConference = $request->source === 'general-conference';

        $talks = Talk::with(['source', 'talkType', 'calling.organization', 'conferenceSession:id,name', 'tags:id,name,slug'])
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
                default => $q->orderByRaw('COALESCE(gc_sessions.session_date, talks.talk_date) ASC')
                    ->orderBy('gc_sessions.display_order')
                    ->orderBy('talks.display_order'),
            })
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Talk $talk) => [
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
            ]);

        return Inertia::render('Talks/Index', [
            'talks' => $talks,
            'sources' => Source::active()->orderBy('name')->get(['id', 'name', 'slug']),
            'conferenceFilters' => $this->conferenceFilterOptions($request, $isGeneralConference),
            'sessionTypes' => GeneralConferenceSessionType::orderBy('display_order')->get(['id', 'name']),
            'filters' => $request->only(['source', 'search', 'year', 'month', 'session', 'session_type', 'tag', 'sort']),
            'activeTag' => $request->tag
                ? Tag::where('slug', $request->tag)->first(['id', 'name', 'slug'])
                : null,
        ]);
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
