<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Seeds a table from a JSON snapshot in database/data/seed/.
 *
 * Rows are inserted with their original primary keys and upserted by `id`, so
 * seeding is idempotent and preserves the foreign-key relationships captured in
 * the snapshot. See docs/PRODUCTION_DATA_SEEDING.md for how the snapshots are
 * regenerated.
 */
trait SeedsFromJson
{
    protected function seedFromJson(string $file, string $table): int
    {
        $path = database_path("data/seed/{$file}");
        $rows = json_decode(file_get_contents($path), true);

        if (! is_array($rows) || $rows === []) {
            return 0;
        }

        $updateColumns = array_values(array_diff(array_keys($rows[0]), ['id']));

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table($table)->upsert($chunk, ['id'], $updateColumns);
        }

        return count($rows);
    }
}
