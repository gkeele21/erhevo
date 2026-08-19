<?php

namespace App\Services;

use App\Models\Talk;
use App\Models\TalkRating;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Two General Conference imports produced a second row for some talks: one
 * fully filed (conference session, real speaking date) and one earlier stub
 * (no session, date synthesized to the 1st of the month). Both carry the same
 * `url`, which is what identifies a pair.
 *
 * Merges each pair onto the row worth keeping — tags, study plan items,
 * ratings, favorites and read dates all move across — then deletes the other.
 * Nothing is guessed: a group with no single fully-filed row is only resolved
 * if KEEP names its winner, and is otherwise reported for a human.
 */
class TalkDeduper
{
    /**
     * Winners for groups the session rule can't settle, pinned by id **and**
     * slug so a diverged database can never lose a legitimate talk — the same
     * safety the 2026_07_28 misfiled-talks migration used.
     *
     * - 53stevenson: id 14 is filed under October 2024 but the url says April.
     * - solemn-assembly: identical session, date and order; the url slug
     *   matches id 2883's fuller title.
     * - 24kearon: id 2559's title is truncated and its date synthesized.
     *
     * @var array<string, array{id: int, slug: string}>
     */
    private const KEEP = [
        'https://www.churchofjesuschrist.org/study/general-conference/2024/04/53stevenson?lang=eng' => [
            'id' => 2632,
            'slug' => 'bridging-the-two-great-commandments-1',
        ],
        'https://www.churchofjesuschrist.org/study/general-conference/1994/10/the-solemn-assembly-sustaining-of-church-officers?lang=eng' => [
            'id' => 2883,
            'slug' => 'the-solemn-assembly-sustaining-of-church-officers',
        ],
        'https://www.churchofjesuschrist.org/study/general-conference/2022/04/24kearon?lang=eng' => [
            'id' => 4184,
            'slug' => 'he-is-risen-with-healing-in-his-wings-we-can-be-more-than-conquerors',
        ],
    ];

    /**
     * Split the duplicate groups into ones with a clear winner and ones needing
     * a human.
     *
     * @return array{
     *     resolved: array<int, array{winner: object, losers: Collection}>,
     *     ambiguous: array<string, Collection>
     * }
     */
    public function plan(): array
    {
        $resolved = [];
        $ambiguous = [];

        foreach ($this->duplicateGroups() as $url => $rows) {
            $winner = $this->pickWinner($url, $rows);

            if (! $winner) {
                $ambiguous[$url] = $rows;

                continue;
            }

            $resolved[] = [
                'winner' => $winner,
                'losers' => $rows->reject(fn ($row) => $row->id === $winner->id)->values(),
            ];
        }

        return ['resolved' => $resolved, 'ambiguous' => $ambiguous];
    }

    /**
     * The row to keep: the only fully-filed one, or the one KEEP names when
     * that rule can't decide.
     */
    private function pickWinner(string $url, Collection $rows): ?object
    {
        $filed = $rows->whereNotNull('general_conference_session_id');

        if ($filed->count() === 1) {
            return $filed->first();
        }

        $override = self::KEEP[$url] ?? null;

        return $override
            ? $rows->first(fn ($row) => $row->id === $override['id'] && $row->slug === $override['slug'])
            : null;
    }

    /**
     * What a merge would touch, without touching it.
     *
     * @param  array<int, array{winner: object, losers: Collection}>  $resolved
     * @return array<string, int>
     */
    public function preview(array $resolved): array
    {
        $loserIds = collect($resolved)->flatMap(fn ($pair) => $pair['losers']->pluck('id'))->all();

        if (! $loserIds) {
            return [];
        }

        return [
            'talks deleted' => count($loserIds),
            'tag links' => DB::table('talk_tag')->whereIn('talk_id', $loserIds)->count(),
            'study plan items' => DB::table('study_plan_items')->whereIn('talk_id', $loserIds)->count(),
            'ratings' => DB::table('talk_ratings')->whereIn('talk_id', $loserIds)->count(),
            'favorites' => DB::table('talk_favorites')->whereIn('talk_id', $loserIds)->count(),
            'read dates' => DB::table('talk_reads')->whereIn('talk_id', $loserIds)->count(),
        ];
    }

    /**
     * @param  array<int, array{winner: object, losers: Collection}>  $resolved
     * @return array<string, int>
     */
    public function merge(array $resolved): array
    {
        $counts = [
            'talks deleted' => 0,
            'tag links' => 0,
            'study plan items' => 0,
            'ratings' => 0,
            'favorites' => 0,
            'read dates' => 0,
        ];

        DB::transaction(function () use ($resolved, &$counts) {
            foreach ($resolved as ['winner' => $winner, 'losers' => $losers]) {
                foreach ($losers as $loser) {
                    $counts['tag links'] += $this->moveTags($winner->id, $loser->id);
                    $counts['study plan items'] += $this->movePlanItems($winner->id, $loser->id);
                    $counts['ratings'] += $this->movePerUser('talk_ratings', $winner->id, $loser->id);
                    $counts['favorites'] += $this->movePerUser('talk_favorites', $winner->id, $loser->id);
                    $counts['read dates'] += $this->moveReads($winner->id, $loser->id);

                    Talk::whereKey($loser->id)->delete();
                    $counts['talks deleted']++;
                }

                // Moved ratings change the surviving talk's cached average.
                TalkRating::refreshTalkAverage($winner->id);
            }
        });

        return $counts;
    }

    /** Talks sharing a url, keyed by that url. @return Collection<string, Collection> */
    private function duplicateGroups(): Collection
    {
        $urls = DB::table('talks')
            ->select('url')
            ->whereNotNull('url')
            ->where('url', '!=', '')
            ->groupBy('url')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('url');

        if ($urls->isEmpty()) {
            return collect();
        }

        return DB::table('talks')
            ->whereIn('url', $urls)
            ->orderBy('id')
            ->get()
            ->groupBy('url');
    }

    /** Union the two talks' tags; the pivot is unique per (talk, tag). */
    private function moveTags(int $winnerId, int $loserId): int
    {
        $existing = DB::table('talk_tag')->where('talk_id', $winnerId)->pluck('tag_id');

        $moved = DB::table('talk_tag')
            ->where('talk_id', $loserId)
            ->when($existing->isNotEmpty(), fn ($q) => $q->whereNotIn('tag_id', $existing->all()))
            ->update(['talk_id' => $winnerId]);

        // Whatever the winner already had is now redundant.
        DB::table('talk_tag')->where('talk_id', $loserId)->delete();

        return $moved;
    }

    /**
     * Re-point plan items so nobody's study plan quietly loses an entry. If the
     * plan already holds the winner, the duplicate entry goes instead.
     */
    private function movePlanItems(int $winnerId, int $loserId): int
    {
        $moved = 0;

        foreach (DB::table('study_plan_items')->where('talk_id', $loserId)->get() as $item) {
            $alreadyPresent = DB::table('study_plan_items')
                ->where('study_plan_id', $item->study_plan_id)
                ->where('talk_id', $winnerId)
                ->exists();

            if ($alreadyPresent) {
                DB::table('study_plan_items')->where('id', $item->id)->delete();

                continue;
            }

            DB::table('study_plan_items')->where('id', $item->id)->update(['talk_id' => $winnerId]);
            $moved++;
        }

        return $moved;
    }

    /** Ratings and favorites are unique per (talk, user) — the winner's wins. */
    private function movePerUser(string $table, int $winnerId, int $loserId): int
    {
        $taken = DB::table($table)->where('talk_id', $winnerId)->pluck('user_id');

        $moved = DB::table($table)
            ->where('talk_id', $loserId)
            ->when($taken->isNotEmpty(), fn ($q) => $q->whereNotIn('user_id', $taken->all()))
            ->update(['talk_id' => $winnerId]);

        DB::table($table)->where('talk_id', $loserId)->delete();

        return $moved;
    }

    /** Read dates are unique per (talk, user, date), so compare on both. */
    private function moveReads(int $winnerId, int $loserId): int
    {
        $moved = 0;

        foreach (DB::table('talk_reads')->where('talk_id', $loserId)->get() as $read) {
            $duplicate = DB::table('talk_reads')
                ->where('talk_id', $winnerId)
                ->where('user_id', $read->user_id)
                ->where('read_on', $read->read_on)
                ->exists();

            if (! $duplicate) {
                DB::table('talk_reads')->where('id', $read->id)->update(['talk_id' => $winnerId]);
                $moved++;

                continue;
            }

            DB::table('talk_reads')->where('id', $read->id)->delete();
        }

        return $moved;
    }
}
