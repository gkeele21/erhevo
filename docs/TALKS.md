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
