<?php

use App\Services\TalkDeduper;
use Illuminate\Database\Migrations\Migration;

/**
 * Two General Conference imports each created a row for 103 talks: one fully
 * filed (conference session, real speaking date) and one earlier stub (no
 * session, date synthesized to the 1st of the month). Both carry the same
 * `url`, so they surfaced as duplicate search results.
 *
 * Merges each pair onto the row worth keeping — tags, study plan items,
 * ratings, favorites and read dates move across first, so no tag is dropped
 * and nobody's study plan loses an entry. See App\Services\TalkDeduper, whose
 * KEEP map pins the three winners the session rule can't settle, matched by
 * id + slug so a diverged database can never lose a legitimate talk.
 *
 * Idempotent: once merged there are no duplicate urls left to find, so a
 * re-run is a no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Guarded so removing the service later leaves old installs migratable.
        if (! class_exists(TalkDeduper::class)) {
            return;
        }

        $deduper = app(TalkDeduper::class);

        $deduper->merge($deduper->plan()['resolved']);
    }

    public function down(): void
    {
        // Data removal is intentional; the stub rows were incomplete duplicates.
    }
};
