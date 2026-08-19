<?php

namespace App\Console\Commands;

use App\Services\TalkDeduper;
use Illuminate\Console\Command;

/**
 * Reporting front end for App\Services\TalkDeduper. Dry run unless --apply, so
 * the merge can always be reviewed before it touches anything.
 */
class DedupeTalks extends Command
{
    protected $signature = 'talks:dedupe
                            {--apply : Perform the merge (without this it only reports)}';

    protected $description = 'Merge talks duplicated across imports, keeping the fully-filed row';

    public function handle(TalkDeduper $deduper): int
    {
        $apply = (bool) $this->option('apply');

        ['resolved' => $resolved, 'ambiguous' => $ambiguous] = $deduper->plan();

        if (! $resolved && ! $ambiguous) {
            $this->info('No duplicate talks found.');

            return self::SUCCESS;
        }

        $groupCount = count($resolved) + count($ambiguous);

        $this->line(sprintf(
            '%d duplicate group%s: %d resolvable, %d needing review.',
            $groupCount,
            $groupCount === 1 ? '' : 's',
            count($resolved),
            count($ambiguous)
        ));

        $counts = $apply ? $deduper->merge($resolved) : $deduper->preview($resolved);

        if ($counts) {
            $this->newLine();
            $this->line(($apply ? 'Moved' : 'Would move') . ':');
            $this->table(
                ['What', 'Rows'],
                collect($counts)->map(fn ($count, $label) => [$label, $count])->values()->all()
            );
        }

        if ($ambiguous) {
            $this->newLine();
            $this->warn(count($ambiguous) . ' group(s) left untouched — no single fully-filed row to keep:');

            foreach ($ambiguous as $url => $rows) {
                $this->line("  {$url}");
                foreach ($rows as $row) {
                    $this->line(sprintf(
                        '    id=%-6s session=%-6s date=%-12s order=%-4s %s',
                        $row->id,
                        $row->general_conference_session_id ?? '-',
                        $row->talk_date ?? '-',
                        $row->display_order ?? '-',
                        $row->title
                    ));
                }
            }

            $this->newLine();
            $this->line('Resolve these by hand (or add them to TalkDeduper::KEEP), then re-run.');
        }

        if (! $apply) {
            $this->newLine();
            $this->info('Dry run — nothing changed. Re-run with --apply to perform the merge.');
        }

        return self::SUCCESS;
    }
}
