# Production Data Seeding — Church Callings, Authors & Talks

How to get the church-calling / author / General-Conference-talk dataset into the
**production** database.

> **TL;DR** — `php artisan migrate --force && php artisan db:seed --force`.
> Everything is seeded deterministically from code + committed data files. No
> scraping, no manual steps, no database dump required.

---

## How it works

The dataset used to require web-scraping and several manual DB edits. It is now
captured as **committed JSON snapshots** in `database/data/seed/`, loaded by
dedicated seeders, so a fresh `migrate + db:seed` reproduces it exactly in any
environment.

`DatabaseSeeder` runs these in dependency order:

| Seeder | Seeds | Source |
|---|---|---|
| `ChurchOrganizationSeeder` | orgs + callings (ids 1–29) | code |
| `ExtraChurchCallingSeeder` | 5 extra calling types (ids 30–35) | `seed/church_callings_extra.json` |
| `SourceSeeder`, `TalkTypeSeeder` | sources, talk types | code |
| `GeneralConferenceSessionTypeSeeder` | session types | code |
| `GeneralConferenceSeeder` | 111 conferences + 555 sessions | code |
| `AuthorSeeder` | 627 authors (all church figures) | `seed/authors.json` |
| `AuthorCallingSeeder` | 744 calling-history rows | `seed/author_callings.json` |
| `TalkSeeder` | 2,580 GC talks (metadata only) | `seed/talks.json` |

The snapshot seeders insert rows with their **original primary keys** and upsert
by `id`, so foreign-key relationships are preserved and re-running `db:seed` is
idempotent. `AuthorSeeder` / `AuthorCallingSeeder` / `TalkSeeder` supersede the
old `ChurchLeadershipSeeder`, the `authors:import` CSV commands, and
`GeneralConferenceTalkSeeder` (those files remain for reference but are no longer
called by `DatabaseSeeder`).

> **Copyright note:** talks store **metadata + source URL only** — no body text
> (GC talks are © Intellectual Reserve). Keep it that way.

---

## Production steps

```bash
# 1. Deploy code, then run migrations
php artisan migrate --force

# 2. Seed (reference data + church figures + talks)
php artisan db:seed --force
```

That's it. `PostSeeder` and the sample test user are gated behind
`! app()->isProduction()`, so they never run in production.

### Verify

```bash
php artisan tinker --execute="
echo 'church_callings:  '.App\Models\ChurchCalling::count().PHP_EOL;   // 34
echo 'authors:          '.App\Models\Author::count().PHP_EOL;          // 627
echo 'author_callings:  '.App\Models\AuthorCalling::count().PHP_EOL;    // 744
echo 'conferences:      '.App\Models\GeneralConference::count().PHP_EOL; // 111
echo 'sessions:         '.App\Models\GeneralConferenceSession::count().PHP_EOL; // 555
echo 'talks:            '.App\Models\Talk::count().PHP_EOL;             // 2580
echo 'talks w/ session: '.App\Models\Talk::whereNotNull('general_conference_session_id')->count().PHP_EOL; // 2480
echo 'talks w/ calling: '.App\Models\Talk::whereNotNull('church_calling_id')->count().PHP_EOL; // 2515
"
```

(In a **dev** environment the author count is 639 — `PostSeeder` adds 12 authors
from sample posts. Production is 627.)

Spot-check a URL:

```bash
php artisan tinker --execute="echo App\Models\Talk::find(2665)->url;"
# → https://www.churchofjesuschrist.org/study/general-conference/2025/04/55kearon?lang=eng
```

The ~100 talks without a session are a **source-data limit**, not a bug — pndr
files them under an "Other Talks" heading with no session. See `docs/TALKS.md`.

---

## Updating the data

If you change church figures or talks locally (via the admin UI, a command, or
tinker) and want the change to reach production:

```bash
# 1. Regenerate the JSON snapshots from your local DB
php artisan db:snapshot-seed-data

# 2. Commit the updated database/data/seed/*.json files
git add database/data/seed/
git commit -m "Refresh seed snapshots"
```

`db:snapshot-seed-data` exports `authors`, `author_callings`, `talks`, and the
extra `church_callings` (ids > 29). Run it from a database that does **not**
contain dev-only `PostSeeder` authors (i.e. not right after a
`migrate:fresh --seed`), or those sample authors will leak into the snapshot.

Deploying to production is then just `migrate --force` + `db:seed --force` again
(the seeders upsert, so it's safe to re-run).

---

## Appendix A — How the data was originally built (historical, reference only)

The snapshots above were produced from this pipeline. You do **not** need to run
it — it is slow (scrapes pndr.me) and includes manual steps. Kept for provenance.

```bash
php artisan migrate:fresh
php artisan db:seed                        # reference data + 39 current leaders

# Author rosters (~500 authors)
php artisan authors:import database/data/quorum-of-the-twelve.csv
php artisan authors:import database/data/general-authority-seventies.csv
php artisan authors:import database/data/area-seventies.csv
php artisan authors:import database/data/first-presidency-historical.csv
#   ⚠ historical FP CSV references calling types (Third/Assistant Counselor, …)
#     added by hand as church_callings ids 30-33.

# Talks (SCRAPES pndr.me — long)
php artisan talks:import-pndr all          # ~2,682 talks (metadata only)
#   ⚠ then manually removed ~102 "Sustaining of …" agenda items
#   ⚠ then created Author records for GC speakers not already present
php artisan talks:link-authors             # link talks → authors by name
php artisan talks:import-pndr-roles        # church_calling_id (calling-at-time); id 35 hand-added
php artisan talks:derive-author-callings   # calling history from talk dates
php artisan talks:rekey-pndr all           # canonical URLs + sessions (SCRAPES — long)
```

See `docs/TALKS.md` for the talks subsystem.
