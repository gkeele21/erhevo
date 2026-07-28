# Adding a New General Conference

Runbook for pulling in a new conference's talks after each April and October
General Conference, once the talks are published on churchofjesuschrist.org
(usually within a few days of the conference).

> **TL;DR** — locally: `sync-conference` → `generate-tags` →
> `db:snapshot-seed-data` → commit. In production: `migrate` + `db:seed` +
> `generate-tags`.

---

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

## Production steps

After deploying the commit with the refreshed snapshots:

```bash
php artisan migrate --force
php artisan db:seed --force          # upserts the new talks/sessions by id
php artisan talks:generate-tags      # tags are generated per environment,
                                     # not shipped in the snapshot
```

Production never scrapes churchofjesuschrist.org — it gets everything from
the committed snapshot except tags, which it generates with the configured
user's AI connection (defaults to the first admin).

---

## Related commands

| Command | What it does |
|---|---|
| `talks:sync-conference {year} {month} [--dry-run]` | Reconcile one conference against churchofjesuschrist.org |
| `talks:import-summaries [--force] [--limit=]` | Fetch talk-page kickers/descriptions for talks missing an excerpt |
| `talks:generate-tags [--user=] [--force] [--limit=]` | AI-tag talks that have no tags yet |
| `talks:link-authors [--relink]` | Link talks to Author records by exact speaker name |
| `db:snapshot-seed-data` | Export authors/callings/conferences/sessions/talks to `database/data/seed/` |

See `docs/PRODUCTION_DATA_SEEDING.md` for how the snapshot seeding works,
and `docs/TALKS.md` for the data model.
