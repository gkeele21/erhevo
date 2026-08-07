<?php

namespace App\Console\Commands;

use App\Models\GeneralConference;
use App\Models\Talk;
use Illuminate\Console\Command;

class SyncLatestTalks extends Command
{
    protected $signature = 'talks:sync-latest
        {--force : Sync the latest conference even if it looks complete}
        {--no-tags : Skip AI tag generation for new talks}';

    protected $description = 'Nightly sync: pull the latest General Conference talks and new BYU Speeches, then tag whatever is new';

    /**
     * How long after a conference we keep re-syncing it nightly. Talks (and
     * corrections) appear over the days following the conference; after this
     * window the conference is treated as settled and skipped.
     */
    private const CONFERENCE_SETTLE_DAYS = 45;

    public function handle(): int
    {
        $failed = false;

        $failed = ! $this->syncLatestConference() || $failed;

        $this->info('Syncing new BYU Speeches…');
        // New speeches land on the first API page (newest first) — one page
        // per night is months of margin.
        $failed = $this->call('talks:import-byu-speeches', ['--pages' => 1]) !== Command::SUCCESS || $failed;

        if (! $this->option('no-tags')) {
            $this->generateTags();
        }

        return $failed ? Command::FAILURE : Command::SUCCESS;
    }

    protected function syncLatestConference(): bool
    {
        [$year, $month] = $this->latestConferencePeriod();

        // The conference shell (and its sessions) may not exist yet right
        // after a season rolls over — the seeder is idempotent, so create it.
        if (! GeneralConference::where('year', $year)->where('month', $month)->exists()) {
            $this->call('db:seed', ['--class' => 'Database\\Seeders\\GeneralConferenceSeeder', '--force' => true]);
        }

        $conference = GeneralConference::where('year', $year)->where('month', $month)->first();

        if (! $conference) {
            $this->warn("Conference {$month} {$year} does not exist yet — skipping GC sync.");

            return true;
        }

        $talkCount = Talk::whereHas('conferenceSession', fn ($q) => $q->where('general_conference_id', $conference->id))->count();
        $settled = $conference->start_date
            && $conference->start_date->diffInDays(now()) > self::CONFERENCE_SETTLE_DAYS
            && $talkCount > 0;

        if ($settled && ! $this->option('force')) {
            $this->info("{$conference->name} is settled ({$talkCount} talks) — skipping. Use --force to re-sync.");

            return true;
        }

        $this->info("Syncing {$conference->name}…");

        return $this->call('talks:sync-conference', ['year' => $year, 'month' => $month]) === Command::SUCCESS;
    }

    /** The most recent conference that should have published talks. */
    protected function latestConferencePeriod(): array
    {
        $now = now();

        if ($now->month >= 10) {
            return [$now->year, 'october'];
        }

        if ($now->month >= 4) {
            return [$now->year, 'april'];
        }

        return [$now->year - 1, 'october'];
    }

    /**
     * AI-tag whatever is newly imported. Best-effort: environments without
     * an AI connection just log and move on.
     */
    protected function generateTags(): void
    {
        try {
            $this->call('talks:generate-tags');
        } catch (\Throwable $e) {
            report($e);
            $this->warn("Tag generation skipped: {$e->getMessage()}");
        }
    }
}
