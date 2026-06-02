<?php

namespace App\Http\Controllers;

use App\Models\GeneralConference;
use App\Models\Source;
use App\Models\Talk;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TalkController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->show_lds_content, 403);

        $isGeneralConference = $request->source === 'general-conference';

        $talks = Talk::with(['source', 'talkType', 'calling'])
            ->when($request->source, fn ($q, $source) => $q->bySource($source))
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
            ->when($request->search, fn ($q, $search) => $q->where(function ($q2) use ($search) {
                $q2->where('title', 'like', "%{$search}%")
                    ->orWhere('speaker_name', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%");
            }))
            ->ordered()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Talk $talk) => [
                'id' => $talk->id,
                'title' => $talk->title,
                'speaker_name' => $talk->speaker_name,
                'speaker_display_name' => $talk->speaker_display_name,
                'summary' => $talk->summary,
                'talk_date' => $talk->talk_date?->format('F Y'),
                'url' => $talk->url,
                'source' => $talk->source?->name,
            ]);

        return Inertia::render('Talks/Index', [
            'talks' => $talks,
            'sources' => Source::active()->orderBy('name')->get(['id', 'name', 'slug']),
            'conferenceFilters' => $this->conferenceFilterOptions($request, $isGeneralConference),
            'filters' => $request->only(['source', 'search', 'year', 'month', 'session']),
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
