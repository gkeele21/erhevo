<?php

namespace App\Console\Commands;

use App\Enums\LessonItemType;
use App\Models\LessonItem;
use Illuminate\Console\Command;

class BackfillLessonScriptureReferences extends Command
{
    protected $signature = 'lessons:backfill-scripture-references
                            {--dry-run : Show what would be linked without writing anything}';

    protected $description = 'Derive scripture reference rows from existing lesson scripture blocks, so passages can find the lessons that use them.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $items = LessonItem::where('type', LessonItemType::Scripture)
            ->with('lesson')
            ->get();

        if ($items->isEmpty()) {
            $this->info('No scripture blocks to process.');

            return self::SUCCESS;
        }

        $linked = 0;
        $skipped = 0;

        foreach ($items as $item) {
            $config = $item->config ?? [];

            if (empty($config['start_chapter_id'])) {
                $skipped++;
                continue;
            }

            $this->line(sprintf(
                '%s "%s" → %s',
                $dryRun ? '[dry-run]' : 'linked',
                str($item->lesson?->title ?? 'Untitled lesson')->limit(40),
                $config['reference'] ?? ('chapter ' . $config['start_chapter_id'])
            ));

            if (! $dryRun) {
                $item->syncScriptureReferencesFromConfig();
            }

            $linked++;
        }

        $this->newLine();
        $this->info(sprintf(
            '%s %d scripture block(s).%s',
            $dryRun ? 'Would link' : 'Linked',
            $linked,
            $skipped ? " Skipped {$skipped} with no chapter set." : ''
        ));

        if ($dryRun) {
            $this->comment('Nothing was written. Re-run without --dry-run to apply.');
        }

        return self::SUCCESS;
    }
}
