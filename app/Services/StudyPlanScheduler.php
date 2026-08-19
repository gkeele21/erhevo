<?php

namespace App\Services;

use App\Models\GeneralConferenceSession;
use App\Models\ScriptureBook;
use App\Models\StudyPlan;
use App\Models\Talk;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StudyPlanScheduler
{
    /**
     * Build the plan's schedule: resolve the reading units (chapters or
     * talks) from the plan's config, split them across sessions, and create
     * the study_plan_items rows.
     *
     * Returns the number of units scheduled (0 means nothing matched the
     * criteria and no items were created).
     */
    public function generate(StudyPlan $plan): int
    {
        $units = $plan->type === 'scripture'
            ? $this->scriptureUnits($plan->config)
            : $this->talkUnits($plan->config);

        if ($units->isEmpty()) {
            return 0;
        }

        // When regenerating (e.g. after an edit), keep completions for
        // readings that survive the rebuild. Keyed by unit, not item id,
        // since all items are recreated.
        $unitColumn = $plan->type === 'scripture' ? 'scripture_chapter_id' : 'talk_id';
        $completed = $plan->items()
            ->whereNotNull('completed_at')
            ->whereNotNull($unitColumn)
            ->pluck('completed_at', $unitColumn);

        $sessions = $this->splitIntoSessions($plan, $units);

        $now = now();
        $rows = [];
        $sortOrder = 0;

        foreach ($sessions as $index => $session) {
            foreach ($session['units'] as $unit) {
                $rows[] = [
                    'study_plan_id' => $plan->id,
                    'session_number' => $index + 1,
                    'sort_order' => $sortOrder++,
                    'scripture_chapter_id' => $plan->type === 'scripture' ? $unit : null,
                    'talk_id' => $plan->type === 'talks' ? $unit : null,
                    'scheduled_date' => $session['date']?->toDateString(),
                    'completed_at' => $completed[$unit] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        $plan->items()->delete();
        foreach (array_chunk($rows, 500) as $chunk) {
            $plan->items()->insert($chunk);
        }

        return $units->count();
    }

    /**
     * Ordered units for a scripture plan — every chapter of the chosen
     * volume, optionally narrowed to specific books — weighted by verse
     * count so long and short chapters can be balanced across sessions.
     */
    protected function scriptureUnits(array $config): Collection
    {
        $books = ScriptureBook::where('volume_id', $config['volume_id'])
            ->when(! empty($config['book_ids']), fn ($q) => $q->whereIn('id', $config['book_ids']))
            ->orderBy('sort_order')
            ->with('chapters:id,book_id,chapter_number,verse_count')
            ->get();

        return $books->flatMap(fn ($book) => $book->chapters->map(fn ($chapter) => [
            'id' => $chapter->id,
            'weight' => max(1, (int) $chapter->verse_count),
        ]))->values();
    }

    /**
     * Ordered talk ids for a talks plan. Three modes:
     *  - author: all talks by one author, optionally limited to the windows
     *    of one or more callings they held (e.g. only while President).
     *  - calling: all talks given while holding a calling (e.g. Apostle),
     *    optionally limited to the last N years.
     *  - conference: every talk from one General Conference, in session
     *    and speaking order.
     */
    protected function talkUnits(array $config): Collection
    {
        if (($config['mode'] ?? null) === 'conference') {
            return GeneralConferenceSession::where('general_conference_id', $config['general_conference_id'])
                ->orderBy('display_order')
                ->with('talks:id,general_conference_session_id,display_order')
                ->get()
                ->flatMap(fn ($session) => $session->talks->pluck('id'))
                ->map(fn ($id) => ['id' => $id, 'weight' => 1])
                ->values();
        }

        $query = Talk::query();

        if (($config['mode'] ?? null) === 'author') {
            $query->where('author_id', $config['author_id']);

            // Talk::withinCallingWindows() is shared with the library filters,
            // so a plan and a library search agree on what a calling covers.
            $callingIds = StudyPlan::callingIdsFromConfig($config);

            if ($callingIds) {
                $query->withinCallingWindows($callingIds);
            }
        } else {
            $query->where('church_calling_id', $config['church_calling_id']);

            if (! empty($config['years_back'])) {
                $query->whereDate('talk_date', '>=', now()->subYears((int) $config['years_back'])->toDateString());
            }
        }

        return $query->orderBy('talk_date')->orderBy('display_order')->pluck('id')
            ->map(fn ($id) => ['id' => $id, 'weight' => 1])
            ->values();
    }

    /**
     * Split units into sessions, each session an array of unit ids plus an
     * optional date:
     *  - start date + frequency + end date: one session per occurrence
     *    between the two dates, units spread by weight (verse count for
     *    scriptures) so each session is a similar amount of reading — a
     *    long chapter stands alone while short ones group together.
     *  - start date + frequency, no end date: one unit per session on
     *    rolling dates until the units run out.
     *  - no dates/frequency: one unit per undated session.
     *
     * @param  Collection<int, array{id: int, weight: int}>  $units
     * @return array<int, array{units: array, date: ?Carbon}>
     */
    protected function splitIntoSessions(StudyPlan $plan, Collection $units): array
    {
        if (! $plan->start_date || ! $plan->frequency) {
            return $units->map(fn ($unit) => ['units' => [$unit['id']], 'date' => null])->all();
        }

        $start = $this->onFrequency($plan->start_date->copy(), $plan->frequency);

        if (! $plan->end_date) {
            $sessions = [];
            $cursor = $start;
            foreach ($units as $unit) {
                $sessions[] = ['units' => [$unit['id']], 'date' => $cursor->copy()];
                $cursor = $this->nextOccurrence($cursor, $plan->frequency);
            }

            return $sessions;
        }

        $dates = [];
        $cursor = $start;
        while ($cursor->lte($plan->end_date) && count($dates) < $units->count()) {
            $dates[] = $cursor->copy();
            $cursor = $this->nextOccurrence($cursor, $plan->frequency);
        }

        // A start date past the end date still gets a single catch-all session.
        if (empty($dates)) {
            $dates[] = $start;
        }

        $sessions = [];
        foreach ($this->weightedChunks($units, count($dates)) as $index => $chunk) {
            $sessions[] = ['units' => $chunk, 'date' => $dates[$index]];
        }

        return $sessions;
    }

    /**
     * Partition units into exactly $count contiguous groups of roughly equal
     * total weight. Greedy walk: each group aims at the average weight of
     * what's left, taking the next unit only while that gets the group
     * closer to its target — so one heavy unit fills a session by itself
     * and several light ones share.
     *
     * Every group gets at least one unit (callers never ask for more groups
     * than units).
     *
     * @return array<int, array<int>> group index => unit ids
     */
    protected function weightedChunks(Collection $units, int $count): array
    {
        $items = $units->values();
        $remainingWeight = $items->sum('weight');

        $groups = [];
        $index = 0;

        for ($group = 0; $group < $count; $group++) {
            $groupsLeft = $count - $group;

            // Last group takes whatever remains.
            if ($groupsLeft === 1) {
                $groups[] = $items->slice($index)->pluck('id')->all();
                break;
            }

            $target = $remainingWeight / $groupsLeft;
            $ids = [];
            $sum = 0;

            while ($index < $items->count()) {
                $unit = $items[$index];

                // Always take one; after that, keep enough units back for
                // the remaining groups and stop once adding the next unit
                // would overshoot the target by more than stopping under it.
                if ($ids !== []) {
                    $unitsLeft = $items->count() - $index;
                    if ($unitsLeft <= $groupsLeft - 1) {
                        break;
                    }
                    if (abs($sum + $unit['weight'] - $target) >= abs($sum - $target)) {
                        break;
                    }
                }

                $ids[] = $unit['id'];
                $sum += $unit['weight'];
                $index++;
            }

            $groups[] = $ids;
            $remainingWeight -= $sum;
        }

        return $groups;
    }

    /** Nudge a date forward onto the frequency's valid days (weekdays only). */
    protected function onFrequency(Carbon $date, string $frequency): Carbon
    {
        if ($frequency === 'weekdays' && $date->isWeekend()) {
            return $date->nextWeekday();
        }

        return $date;
    }

    protected function nextOccurrence(Carbon $date, string $frequency): Carbon
    {
        return match ($frequency) {
            'weekly' => $date->copy()->addWeek(),
            'weekdays' => $date->copy()->nextWeekday(),
            default => $date->copy()->addDay(),
        };
    }
}
