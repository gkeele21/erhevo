<?php

namespace App\Services;

use App\Models\Author;
use App\Models\AuthorCalling;
use App\Models\ChurchCalling;
use App\Models\GeneralConference;
use Illuminate\Support\Collection;

/**
 * Picker options shared by the study plan builder and the library filters, so
 * both offer the same way of narrowing talks: by author (optionally limited to
 * one of their callings), by church calling, or by conference.
 */
class TalkFilterOptions
{
    /** Every calling, labelled with its organization. Small enough to always ship. */
    public function churchCallings(): Collection
    {
        return ChurchCalling::with('organization')
            ->orderBy('church_organization_id')
            ->orderBy('name')
            ->get()
            ->map(fn (ChurchCalling $calling) => [
                'id' => $calling->id,
                'label' => $calling->display_label,
            ])
            ->values();
    }

    /** Conferences that actually have talks, newest first. */
    public function conferences(): Collection
    {
        return GeneralConference::whereHas('talks')
            ->orderByDesc('start_date')
            ->get(['id', 'name'])
            ->map(fn (GeneralConference $conference) => [
                'id' => $conference->id,
                'name' => $conference->name,
            ])
            ->values();
    }

    /**
     * Every author with talks, each with their calling history. This is a large
     * payload (over a thousand authors), so it suits a one-off form rather than
     * a page that reloads on every filter change — see searchAuthors() for that.
     */
    public function authorsWithCallings(): Collection
    {
        return Author::whereHas('talks')
            ->with(['callings.calling.organization'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn (Author $author) => [
                'id' => $author->id,
                'name' => $author->full_name,
                'callings' => $this->callingPeriods($author->callings),
            ])
            ->values();
    }

    /** Type-ahead search over authors who have talks in the library. */
    public function searchAuthors(string $term, int $limit = 10): Collection
    {
        return Author::whereHas('talks')
            ->search($term)
            ->with('calling')
            ->orderBy('last_name')
            ->orderBy('display_name')
            ->limit($limit)
            ->get()
            ->map(fn (Author $author) => [
                'id' => $author->id,
                'name' => $author->full_name,
                'calling' => $author->calling?->name,
            ])
            ->values();
    }

    /** One author's calling windows, for the "limit to a calling" sub-filter. */
    public function callingsForAuthor(int $authorId): Collection
    {
        $author = Author::with(['callings.calling.organization'])->find($authorId);

        return $author ? $this->callingPeriods($author->callings) : collect();
    }

    private function callingPeriods(Collection $callings): Collection
    {
        return $callings->map(fn (AuthorCalling $calling) => [
            'id' => $calling->id,
            'label' => $calling->calling?->display_label ?? 'Calling',
            'start_date' => $calling->start_date?->toDateString(),
            'end_date' => $calling->end_date?->toDateString(),
        ])->values();
    }
}
