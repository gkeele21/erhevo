# Come Follow Me Feature

## Overview

The Come Follow Me (CFM) feature allows LDS members to track their scripture study according to the church's official study program. This feature enables:

- **Scripture Study Tracking**: Browse the weekly CFM study schedule
- **Content Association**: Attach posts (notes, stories, thoughts) to specific scripture passages
- **4-Year Resurfacing**: See past content resurface when the same scriptures are studied again
- **Publisher Resources**: Browse external content (videos, podcasts) from popular CFM publishers

## Study Schedule

The CFM program follows a 4-year rotation:
- Year 1: Book of Mormon
- Year 2: Doctrine and Covenants
- Year 3: Old Testament + Pearl of Great Price
- Year 4: New Testament

Each year contains ~52 weekly study assignments, with each week covering specific chapters or special topics (like Christmas or Easter).

## Key Concepts

### Scripture Structure
- **Volumes**: The 5 standard works (Book of Mormon, D&C, OT, NT, Pearl of Great Price)
- **Books**: Individual books within each volume (1 Nephi, Genesis, etc.)
- **Chapters**: Chapters within each book
- **Verses**: Individual verses (stored for reference, not content due to copyright)

### Study Schedule
- **Study Years**: Annual curriculum (2024, 2025, etc.)
- **Weeks**: 52 weekly assignments per year
- **Special Topics**: Non-scripture weeks (Christmas, Easter, General Conference)

### Content Resurfacing
Posts resurface through **chapter matching**:
1. A post created about "1 Nephi 3:7" in 2024
2. Will automatically appear when 1 Nephi 3 is studied in 2028
3. Respects visibility settings (public, private, friends)

## Related Documentation

- [CFM_DATABASE_SCHEMA.md](CFM_DATABASE_SCHEMA.md) - Database structure
- [CFM_SCRIPTURE_REFERENCES.md](CFM_SCRIPTURE_REFERENCES.md) - Scripture reference system
- [CFM_RESURFACING.md](CFM_RESURFACING.md) - Content resurfacing mechanism
- [CFM_PUBLISHERS.md](CFM_PUBLISHERS.md) - Publisher system
- [CFM_SEEDING.md](CFM_SEEDING.md) - Seeding scripture and schedule data

## Models

| Model | Description |
|-------|-------------|
| `ScriptureVolume` | The 5 standard works |
| `ScriptureBook` | Books within volumes |
| `ScriptureChapter` | Chapters within books |
| `ScriptureVerse` | Individual verses |
| `CfmStudyYear` | Annual study curriculum |
| `CfmWeek` | Weekly study assignments |
| `CfmSpecialTopic` | Recurring special topics |
| `CfmPublisher` | External content creators |
| `CfmPublisherContent` | Links to external content |
| `PostScriptureReference` | Links posts to scriptures |

## Routes (Planned)

| Route | Description |
|-------|-------------|
| `GET /come-follow-me` | CFM landing page, current week |
| `GET /come-follow-me/schedule` | Full year schedule |
| `GET /come-follow-me/week/{slug}` | Week detail page |
| `GET /come-follow-me/publishers` | Publisher directory |
| `GET /come-follow-me/publishers/{slug}` | Publisher detail |

## Copyright Notice

No copyrighted scripture text is stored in the database. Only structural information (volumes, books, chapters, verse counts) is stored. Users are linked to the official Church website for scripture content.
