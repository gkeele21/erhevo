# Talks Feature

This document describes the unified talks tracking functionality for content from various sources.

## Overview

The talks feature provides a unified structure for tracking talks, speeches, and articles from multiple sources:

- **General Conference** - Semi-annual General Conference talks
- **BYU Speeches** - Devotionals, forums, and addresses from BYU
- **BYU-Idaho Devotionals** - Devotionals from BYU-Idaho
- **Ensign/Liahona** - Magazine articles
- **CES Firesides** - Church Educational System events
- And more...

## Database Tables

| Table | Description |
|-------|-------------|
| `sources` | Content sources (General Conference, BYU Speeches, etc.) |
| `talk_types` | Types of talks (Conference Talk, Devotional, Forum, etc.) |
| `talks` | Unified table for all talks from any source |
| `general_conferences` | Conference records (year, month, dates) |
| `general_conference_session_types` | Session type definitions |
| `general_conference_sessions` | Individual sessions per conference |
| `talk_ratings` | One 1–5 star rating per user per talk |
| `talk_favorites` | Talks a user starred |
| `talk_reads` | Dates a user read a talk (one row per date) |

## Running the Seeders

### Initial Setup

Run seeders in order:

```bash
# Create church organizations and callings (for speaker titles)
php artisan db:seed --class=ChurchOrganizationSeeder

# Create sources (General Conference, BYU Speeches, etc.)
php artisan db:seed --class=SourceSeeder

# Create talk types (Conference Talk, Devotional, etc.)
php artisan db:seed --class=TalkTypeSeeder

# Create General Conference session types
php artisan db:seed --class=GeneralConferenceSessionTypeSeeder

# Create all conferences from 1971 to present
php artisan db:seed --class=GeneralConferenceSeeder

# Import General Conference talks from JSON
php artisan db:seed --class=GeneralConferenceTalkSeeder
```

## Import Commands

### General Conference Talks

```bash
# Import from default location (database/data/general_conference_talks.json)
php artisan gc:import-talks

# Import from a custom file
php artisan gc:import-talks /path/to/talks.json
```

### Talks from Other Sources

```bash
# Import BYU speeches
php artisan talks:import byu-speeches

# Import from a specific file
php artisan talks:import byu-speeches /path/to/speeches.json

# Import from any source
php artisan talks:import {source-slug} {file?}
```

## JSON File Formats

### General Conference Talks

File: `database/data/general_conference_talks.json`

```json
{
    "2024": {
        "october": {
            "saturday-morning": [
                {
                    "speaker": "Russell M. Nelson",
                    "title": "The Lord Jesus Christ Will Come Again",
                    "slug": "the-lord-jesus-christ-will-come-again",
                    "calling_prefix": "President",
                    "organization": "The First Presidency",
                    "summary": "Optional summary"
                }
            ],
            "saturday-afternoon": [...],
            "priesthood": [...],
            "sunday-morning": [...],
            "sunday-afternoon": [...]
        },
        "april": {...}
    }
}
```

### Other Sources (BYU Speeches, etc.)

File: `database/data/{source-slug}_talks.json`

```json
{
    "2024": [
        {
            "type": "devotional",
            "speaker": "Jeffrey R. Holland",
            "title": "The Ministry of Angels",
            "slug": "the-ministry-of-angels",
            "date": "2024-09-10",
            "calling_prefix": "Elder",
            "organization": "The Quorum of the Twelve Apostles",
            "summary": "Optional summary"
        },
        {
            "type": "forum",
            "speaker": "C. Shane Reese",
            "title": "The Divine Gift of Agency",
            "slug": "the-divine-gift-of-agency",
            "date": "2024-01-16",
            "speaker_title": "President, Brigham Young University"
        }
    ]
}
```

## Available Sources

| Slug | Name | Base URL |
|------|------|----------|
| `general-conference` | General Conference | churchofjesuschrist.org/study/general-conference |
| `byu-speeches` | BYU Speeches | speeches.byu.edu |
| `byui-devotionals` | BYU-Idaho Devotionals | byui.edu/devotionals |
| `ensign` | Ensign | churchofjesuschrist.org/study/ensign |
| `liahona` | Liahona | churchofjesuschrist.org/study/liahona |
| `new-era` | New Era | churchofjesuschrist.org/study/new-era |
| `fsy` | For the Strength of Youth | churchofjesuschrist.org/study/for-the-strength-of-youth |
| `friend` | Friend | churchofjesuschrist.org/study/friend |
| `ces-firesides` | CES Firesides | churchofjesuschrist.org/study/broadcasts |

## Talk Types

| Slug | Name |
|------|------|
| `conference-talk` | Conference Talk |
| `devotional` | Devotional |
| `forum` | Forum |
| `fireside` | Fireside |
| `commencement` | Commencement |
| `education-week` | Education Week |
| `womens-conference` | Women's Conference |
| `article` | Article |
| `message` | Message |
| `other` | Other |

## Models and Relationships

### Source
```php
$source = Source::where('slug', 'byu-speeches')->first();
$source->talks;           // All talks from this source
$source->generateTalkUrl($slug, $params);  // Generate URL for a talk
```

### TalkType
```php
$type = TalkType::where('slug', 'devotional')->first();
$type->talks;  // All talks of this type
```

### Talk
```php
// Get all talks
$talks = Talk::ordered()->get();

// Filter by source
$gcTalks = Talk::bySource('general-conference')->get();
$byuTalks = Talk::bySource('byu-speeches')->get();

// Filter by speaker
$hollandTalks = Talk::bySpeaker('Holland')->get();

// Filter by year
$talks2024 = Talk::byYear(2024)->get();

// General Conference talks only
$gcOnly = Talk::generalConference()->get();

// Access relationships
$talk->source;              // Source (General Conference, BYU, etc.)
$talk->talkType;            // Type (Devotional, Conference Talk, etc.)
$talk->conferenceSession;   // GC Session (if applicable)
$talk->conference;          // GC Conference (via session)
$talk->calling;             // Church calling (for title)
$talk->organization;        // Church organization

// Computed attributes
$talk->speaker_display_name; // "President Russell M. Nelson"
$talk->year;                 // Year from talk_date
$talk->isGeneralConferenceTalk(); // true/false
```

### GeneralConference
```php
$conference = GeneralConference::where('year', 2024)->october()->first();
$conference->sessions;  // All sessions
$conference->talks;     // All talks (through sessions)
```

## Reader Engagement

Signed-in users can rate, favorite and log their readings of any library talk.
All three are personal: a talk card only ever shows the current user's own
rating, star and read dates, alongside the public average.

### Ratings

Ratings are 1–5 stars, one per user per talk. The average is **cached on the
talks table** (`average_rating`, `ratings_count`) rather than aggregated per
query, so the library can filter and sort on it across the whole library.
`App\Models\TalkRating` keeps the cache in step on every save and delete —
which means the cache stays correct from imports, tinker and tests too, not
just from the controller.

Because the cache is maintained by model events, **mass deletes bypass it**.
Delete ratings through the model (`$rating->delete()`), or call
`TalkRating::refreshTalkAverage($talkId)` afterwards.

```php
$talk->ratings;             // every user's rating
$talk->average_rating;      // cached mean, null when unrated
$talk->ratings_count;       // cached number of ratings
$talk->favorites;           // TalkFavorite rows
$talk->reads;               // TalkRead rows (all users)

$user->talkRatings;         // this user's ratings
$user->favoriteTalks;       // Talk models this user starred
$user->talkReads;           // every date this user logged

Talk::minRating(4)->get();          // average of 4+ stars
Talk::favoritedBy($user->id)->get();
Talk::readBy($user->id)->get();
```

### Read dates

A talk can be read more than once, so `talk_reads` holds one row per date and
the card lists every date, newest first. The unique key is
`(talk_id, user_id, read_on)` — logging the same date twice is a no-op rather
than an error. Future dates are rejected.

### Library filters and the random pick

| Query parameter | Effect |
|-----------------|--------|
| `search` | Title, speaker name, or summary |
| `source` | Source slug |
| `tag` | Tag slug |
| `author_id` | Talks linked to one Author entity |
| `author_calling_ids[]` | Narrows `author_id` to talks given while they held **any** of these callings (repeatable) |
| `church_calling_id` | Talks by anyone holding that calling when they spoke |
| `general_conference_id` | Every talk from one named conference |
| `years_back=1..100` | Talks dated within the last N years |
| `year` / `month` / `session` / `session_type` | General Conference cascade (needs `source=general-conference`) |
| `min_rating=1..5` | Talks whose cached average is at least this many stars |
| `favorites=1` | Only the current user's favorites (ignored for guests) |
| `sort=rating` | Highest average first; unrated talks sort last |

The speaker, calling, conference and recency filters deliberately mirror the
study plan builder's talk criteria. The calling-window logic is genuinely
shared: both the library filter and `StudyPlanScheduler::talkUnits()` call
`Talk::withinCallingWindows()`, so the two can't drift apart.

Bad input is ignored rather than rejected, so a hand-edited URL degrades to a
broader search instead of a 422: out-of-range `min_rating` and `years_back`,
and calling-window ids that don't exist. A bare `author_calling_id=N` is still
accepted, so links made while the filter was single-select keep working.

### Calling windows

`Talk::withinCallingWindows(array $authorCallingIds)` selects talks dated inside
**any** of the given windows — they union, so "as an Apostle or in the First
Presidency" returns both spans and nothing in the gap between them. Two edges
worth knowing:

- An **undated** talk sits inside no window, so it drops out whenever any window
  is applied.
- A window with **neither bound** covers every dated talk, and short-circuits
  the rest of the OR group.

Study plans store the selection as `config.author_calling_ids`. Plans saved
before this was multi-select hold a single `config.author_calling_id`;
`StudyPlan::callingIdsFromConfig()` normalizes both shapes and is the only place
that needs to know, feeding the scheduler, the criteria summary, and the
`author_calling_ids` accessor the edit form prefills from.

### Where the picker options come from

`App\Services\TalkFilterOptions` builds these lists for both the library
filters and the study plan builder:

| Method | Used by | Notes |
|--------|---------|-------|
| `churchCallings()` | both | ~34 rows, ships with the page |
| `conferences()` | both | ~111 rows, ships with the page |
| `authorsWithCallings()` | study plan form only | Over a thousand authors — a one-off form payload |
| `searchAuthors($term)` | library filters | Type-ahead via `GET /api/library/authors`, min 2 characters |
| `callingsForAuthor($id)` | library filters | Only the selected author's windows |

The library searches authors instead of shipping them because it reloads on
every filter change; the bulk list would dominate the payload. With an author
and a recency window applied, the whole props payload is about 25&nbsp;KB.

`/library/favorites` is the same page with `favorites=1` pre-applied, so the
normal filters still layer on top of a user's favorites list.

Every library response also includes a `randomTalk` prop — one talk drawn at
random from **the same filtered set** as the results, for readers who don't have
a particular talk in mind. `TalkController::filteredTalks()` exists so the
paginated list and the random pick can never drift apart; the Shuffle button
re-rolls it with an Inertia partial reload (`only: ['randomTalk']`).

### Routes

| Method | URI | Name |
|--------|-----|------|
| GET | `/api/library/authors` | `talks.authors.search` |
| GET | `/library/favorites` | `talks.favorites` |
| PUT | `/library/talks/{talk}/rating` | `talks.rate` |
| DELETE | `/library/talks/{talk}/rating` | `talks.rating.destroy` |
| POST | `/library/talks/{talk}/favorite` | `talks.favorite` |
| POST | `/library/talks/{talk}/reads` | `talks.reads.store` |
| DELETE | `/library/talks/{talk}/reads/{read}` | `talks.reads.destroy` |

All except the author type-ahead (public, like the library itself) require
authentication, and all honor the user's `show_lds_content` setting,
the same gate as the library itself. They redirect back so the page re-renders
from fresh server state instead of reconciling an optimistic update.

## Duplicate rows from re-imports

Two General Conference imports each created a row for 103 talks: one fully
filed (conference session, real speaking date) and one earlier stub (no
session, date synthesized to the 1st of the month). Both carried the **same
`url`**, which is what identifies a pair — they showed up as duplicate search
results in the library.

Cleaned up by `2026_08_19_000005_merge_duplicate_import_talks`, which runs
`App\Services\TalkDeduper`. **If a re-import ever reintroduces duplicates, run
the command rather than deleting rows by hand:**

```bash
php artisan talks:dedupe          # report only — always start here
php artisan talks:dedupe --apply  # perform the merge
```

It *merges* rather than deletes: tags union onto the surviving row, and study
plan items, ratings, favorites and read dates are re-pointed before the stub is
removed, each respecting its unique key. A plain `DELETE` would cascade — the
103 stubs carried 480 tag links and one sat in a user's study plan.

The winner is the group's only fully-filed row. When that can't decide (both
rows have a session), `TalkDeduper::KEEP` names the winner by **id + slug** —
the same safety the earlier `remove_misfiled_seed_talks` migration used, so a
diverged database can never lose a legitimate talk. Groups that are neither
resolvable nor listed in `KEEP` are reported and left alone.

`database/data/seed/talks.json` was edited to drop the same 103 rows, so a
fresh install doesn't reintroduce them.

> **Careful with `db:snapshot-seed-data`.** Regenerating the snapshots today
> rewrites every `created_at`/`updated_at` six hours earlier than what's
> committed — the checked-in files were exported under a different timezone.
> That turns a small data change into a ~34,000-line diff across all six files.
> Prefer a targeted edit unless you actually intend to re-baseline them.

## Adding New Sources

1. Add the source to `SourceSeeder.php`:
   ```php
   [
       'name' => 'My New Source',
       'slug' => 'my-new-source',
       'base_url' => 'https://example.com',
       'platform' => 'example.com',
       'description' => 'Description of the source',
   ],
   ```

2. Run the seeder:
   ```bash
   php artisan db:seed --class=SourceSeeder
   ```

3. Create a JSON file at `database/data/my-new-source_talks.json`

4. Import the talks:
   ```bash
   php artisan talks:import my-new-source
   ```

## URL Generation

The `Source` model can generate URLs for talks based on the source's URL pattern:

```php
$source = Source::where('slug', 'general-conference')->first();
$url = $source->generateTalkUrl('the-lord-jesus-christ-will-come-again', [
    'year' => 2024,
    'month' => 'october',
]);
// https://www.churchofjesuschrist.org/study/general-conference/2024/10/the-lord-jesus-christ-will-come-again?lang=eng

$byuSource = Source::where('slug', 'byu-speeches')->first();
$url = $byuSource->generateTalkUrl('the-ministry-of-angels');
// https://speeches.byu.edu/talks/the-ministry-of-angels/
```

## Speaker Information

Speakers can be identified in two ways:

1. **Church Leaders** - Use `calling_prefix` and `organization`:
   ```json
   {
       "speaker": "Jeffrey R. Holland",
       "calling_prefix": "Elder",
       "organization": "The Quorum of the Twelve Apostles"
   }
   ```
   Display: "Elder Jeffrey R. Holland"

2. **Other Speakers** - Use `speaker_title`:
   ```json
   {
       "speaker": "C. Shane Reese",
       "speaker_title": "President, Brigham Young University"
   }
   ```
   Display: "C. Shane Reese, President, Brigham Young University"

## Church Organizations

Use these exact organization names for `calling_prefix` matching:

- `The First Presidency`
- `The Quorum of the Twelve Apostles`
- `The Presidency of the Seventy`
- `The Presiding Bishopric`
- `General Authority Seventies`
- `Area Seventies`
- `Primary General Presidency`
- `Relief Society General Presidency`
- `Sunday School General Presidency`
- `Young Women General Presidency`
- `Young Men General Presidency`

## General Conference Session Slugs

- `saturday-morning`
- `saturday-afternoon`
- `saturday-evening`
- `priesthood`
- `womens`
- `sunday-morning`
- `sunday-afternoon`
