# Keeping the Talk Library Current

> **TL;DR — it's automatic.** `talks:sync-latest` runs nightly at 03:15 via
> the Laravel scheduler: it syncs the latest General Conference while it's
> fresh (up to 45 days after it starts), pulls new BYU Speeches, and AI-tags
> whatever arrived. The sections below are for understanding it, running it
> by hand, and refreshing the committed seed snapshots.

## Nightly automation

- Defined in `routes/console.php`; the command is
  `app/Console/Commands/SyncLatestTalks.php`. Output logs to
  `storage/logs/talks-sync.log`.
- **Server requirement**: the standard Laravel scheduler cron must be
  running (`php artisan schedule:run` every minute — Forge's Scheduler
  panel sets this up). Verify with `php artisan schedule:list`.
- It creates the new conference shell automatically when a season rolls
  over (via `GeneralConferenceSeeder`, which is idempotent).
- Tagging is best-effort: if the environment has no AI connection it warns
  and moves on; re-run `talks:generate-tags` later.
- `--force` re-syncs a settled conference; `--no-tags` skips tagging.
- Nightly synced talks exist only in that environment until the seed
  snapshots are refreshed (below). That's fine: seeding upserts, and MySQL
  matches on the talks' unique (source, slug) key, so a later `db:seed`
  updates rather than duplicates them.

## Manual runs & seeding new environments

The snapshots in `database/data/seed/` are what fresh environments seed
from, so refresh and commit them occasionally (e.g. after each conference):

---

# General Conference

## Local steps

```bash
# 0. Make sure the conference + session shells exist.
#    GeneralConferenceSeeder computes conferences up to the current year,
#    so this is only needed the first time in a new year/half.
php artisan db:seed --class=GeneralConferenceSeeder

# 1. Sync the conference from churchofjesuschrist.org.
#    Adds every talk (all speakers), fixes session assignment and speaking
#    order, pulls each talk's excerpt, links authors, and fills the
#    speaker's calling from their calling history. Idempotent — safe to
#    re-run. Use --dry-run first if you want to preview.
php artisan talks:sync-conference 2026 october

# 2. Generate AI tags for the new talks (uses your AI connection from
#    Profile → AI Connection; skips talks that already have tags).
php artisan talks:generate-tags --user=you@example.com

# 3. Refresh the committed seed snapshots so every environment gets the
#    new talks (talks + conferences + sessions ship as JSON in
#    database/data/seed/).
php artisan db:snapshot-seed-data

# 4. Commit the updated snapshot files.
git add database/data/seed && git commit -m "Add October 2026 conference talks"
```

### Watch the sync output

- **"Skipping unknown session type"** — the page has a session whose type
  isn't in `GeneralConferenceSessionTypeSeeder`. One-off broadcasts
  (firesides, satellite events) are skipped on purpose; if it's a real new
  recurring session type, add it to the seeder, re-seed, and re-run the sync.
- **"had no author match"** — a new speaker (e.g. a newly called Seventy)
  has no `Author` record yet. Create the author (Admin → Authors) with
  their calling, then re-run `php artisan talks:link-authors` and
  `php artisan talks:sync-conference <year> <month>` to fill the talk's
  calling. Institutional items like the Church Auditing Department report
  stay unlinked — that's fine.

### Verify

```bash
php artisan tinker --execute="
\$c = App\Models\GeneralConference::where('year', 2026)->where('month', 'october')->first();
print(\$c->name . ': ' . App\Models\Talk::whereHas('conferenceSession', fn (\$q) => \$q->where('general_conference_id', \$c->id))->count() . ' talks');
"
```

A modern conference has roughly 30–40 talks across 4–5 sessions. Spot-check
the Library page: the conference's sessions should read as blocks in
speaking order, with speaker callings and excerpts on each card.

---

# BYU Speeches

## Local steps

```bash
# 1. Import from the speeches.byu.edu API. Idempotent and incremental —
#    keyed by slug, it only creates what's new and never overwrites a
#    curated excerpt. Safe to re-run any time.
php artisan talks:import-byu-speeches

# 2. Refresh the snapshots and commit (same as conferences).
php artisan db:snapshot-seed-data
git add database/data/seed && git commit -m "Update BYU Speeches"
```

The import brings everything with it, so there are usually **no follow-up
steps**:

- **Excerpts** come from the site's own descriptions (a few very old
  speeches have none — that's expected, not a failure).
- **Topic tags** come from BYU's topic taxonomy (faith, adversity, …), so
  `talks:generate-tags` isn't needed for these — though running it is
  harmless; it skips already-tagged talks.
- **Authors**: speakers already in the system are linked; unknown speakers
  get Author records created (that's why `authors.json` changes — commit
  it). Pass `--no-authors` to skip creating new authors.
- **Callings** are filled from author calling history where unambiguous
  (an apostle's devotional shows the calling they held that day).

### Verify

```bash
php artisan tinker --execute="
print('BYU talks: ' . App\Models\Talk::whereHas('source', fn (\$q) => \$q->where('slug', 'byu-speeches'))->count());
"
```

Spot-check the Library filtered to BYU Speeches: newest devotional on top
(newest-first sort), with speaker, type, topic tags, and excerpt.

---

# Production steps (both flows)

Day to day, production keeps itself current via the nightly
`talks:sync-latest`. These steps are only needed when deploying a
refreshed snapshot (e.g. standing up a new environment or after a bulk
local resync):

```bash
php artisan migrate --force
php artisan db:seed --force               # upserts talks/authors/sessions
                                          # (matches existing talks by their
                                          # unique source+slug — no dupes)

# Tags are per-environment (the tags table is shared with user posts, so
# tag rows can't ship in the snapshot without colliding with prod ids):
php artisan talks:import-byu-speeches     # re-attaches BYU topic tags to the
                                          # seeded talks (fast API pull, no AI)
php artisan talks:generate-tags           # AI-tags whatever still has none
                                          # (conference talks, mostly)
```

---

## Related commands

| Command | What it does |
|---|---|
| `talks:sync-latest [--force] [--no-tags]` | The nightly job: latest conference + new BYU Speeches + tags |
| `talks:sync-conference {year} {month} [--dry-run]` | Reconcile one conference against churchofjesuschrist.org |
| `talks:import-byu-speeches [--pages=] [--no-authors]` | Import/refresh all BYU Speeches from the speeches.byu.edu API |
| `talks:import-summaries [--force] [--limit=]` | Fetch talk-page kickers/descriptions for talks missing an excerpt |
| `talks:generate-tags [--user=] [--force] [--limit=]` | AI-tag talks that have no tags yet |
| `talks:link-authors [--relink]` | Link talks to Author records by exact speaker name |
| `db:snapshot-seed-data` | Export authors/callings/conferences/sessions/talks to `database/data/seed/` |

See `docs/PRODUCTION_DATA_SEEDING.md` for how the snapshot seeding works,
and `docs/TALKS.md` for the data model.
