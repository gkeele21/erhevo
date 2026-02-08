# CFM Database Schema

## Entity Relationship Diagram

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           SCRIPTURE STRUCTURE                                    │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌──────────────────┐    ┌──────────────────┐    ┌──────────────────┐          │
│  │ scripture_volumes│───<│ scripture_books  │───<│scripture_chapters│          │
│  └──────────────────┘    └──────────────────┘    └────────┬─────────┘          │
│                                                           │                     │
│                                                  ┌────────▼─────────┐          │
│                                                  │ scripture_verses │          │
│                                                  └──────────────────┘          │
└─────────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────────┐
│                           CFM SCHEDULE                                           │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌──────────────────┐    ┌─────────────────────┐    ┌──────────────────┐       │
│  │ cfm_study_years  │───<│cfm_study_year_volumes│    │cfm_special_topics│       │
│  └────────┬─────────┘    └─────────────────────┘    └────────┬─────────┘       │
│           │                                                   │                 │
│           └───────>┌──────────────────┐<──────────────────────┘                │
│                    │    cfm_weeks     │                                         │
│                    └────────┬─────────┘                                         │
│                             │                                                   │
│              ┌──────────────┴──────────────┐                                   │
│              │                             │                                    │
│    ┌─────────▼─────────┐    ┌──────────────▼───────────┐                       │
│    │ cfm_week_chapters │    │    cfm_week_topics       │                       │
│    └───────────────────┘    └──────────────────────────┘                       │
└─────────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────────┐
│                          USER CONTENT                                            │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌──────────────────┐         ┌────────────────────────────┐                   │
│  │      posts       │────────<│ post_scripture_references  │                   │
│  └────────┬─────────┘         └────────────────────────────┘                   │
│           │                                                                     │
│           │    ┌──────────────────┐                                            │
│           └───>│ post_cfm_weeks   │                                            │
│                └──────────────────┘                                            │
└─────────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────────┐
│                          EXTERNAL PUBLISHERS                                     │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌──────────────────┐         ┌────────────────────────┐                        │
│  │  cfm_publishers  │────────<│ cfm_publisher_content  │                        │
│  └──────────────────┘         └────────────────────────┘                        │
└─────────────────────────────────────────────────────────────────────────────────┘
```

## Tables

### Scripture Structure

#### `scripture_volumes`
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | string | Full name (e.g., "Book of Mormon") |
| slug | string | URL-friendly slug |
| abbreviation | string(10) | Short form (e.g., "BoM") |
| sort_order | tinyint | Display order |

#### `scripture_books`
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| volume_id | bigint | FK to scripture_volumes |
| name | string | Book name (e.g., "1 Nephi") |
| slug | string | URL-friendly slug |
| abbreviation | string(20) | Short form (e.g., "1 Ne.") |
| sort_order | smallint | Order within volume |
| chapter_count | smallint | Number of chapters |

#### `scripture_chapters`
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| book_id | bigint | FK to scripture_books |
| chapter_number | smallint | Chapter number |
| verse_count | smallint | Number of verses |

#### `scripture_verses`
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| chapter_id | bigint | FK to scripture_chapters |
| verse_number | smallint | Verse number |

### CFM Schedule

#### `cfm_study_years`
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| year | year | Calendar year (unique) |
| title | string | Display title |
| description | text | Optional description |

#### `cfm_study_year_volumes`
Pivot table linking years to volumes being studied.

| Column | Type | Description |
|--------|------|-------------|
| study_year_id | bigint | FK to cfm_study_years |
| volume_id | bigint | FK to scripture_volumes |

#### `cfm_special_topics`
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | string | Topic name (e.g., "Christmas") |
| slug | string | URL-friendly slug |
| description | text | Optional description |

#### `cfm_weeks`
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| study_year_id | bigint | FK to cfm_study_years |
| week_number | tinyint | Week 1-52 |
| start_date | date | Week start |
| end_date | date | Week end |
| title | string | Display title (e.g., "1 Nephi 1-7") |
| slug | string | URL-friendly slug |
| is_special_topic | boolean | True for Christmas, Easter, etc. |
| description | text | Optional description |

#### `cfm_week_chapters`
Pivot table linking weeks to chapters.

| Column | Type | Description |
|--------|------|-------------|
| cfm_week_id | bigint | FK to cfm_weeks |
| chapter_id | bigint | FK to scripture_chapters |

#### `cfm_week_topics`
Pivot table linking weeks to special topics.

| Column | Type | Description |
|--------|------|-------------|
| cfm_week_id | bigint | FK to cfm_weeks |
| special_topic_id | bigint | FK to cfm_special_topics |

### User Content

#### `post_scripture_references`
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| post_id | bigint | FK to posts |
| start_chapter_id | bigint | FK to scripture_chapters |
| start_verse | smallint | Starting verse (nullable) |
| end_chapter_id | bigint | Ending chapter for ranges (nullable) |
| end_verse | smallint | Ending verse (nullable) |
| sort_order | tinyint | Order of references |

#### `post_cfm_weeks`
Pivot table linking posts to CFM weeks.

| Column | Type | Description |
|--------|------|-------------|
| post_id | bigint | FK to posts |
| cfm_week_id | bigint | FK to cfm_weeks |

### Publishers

#### `cfm_publishers`
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | string | Publisher name |
| slug | string | URL-friendly slug |
| description | text | About the publisher |
| website_url | string | Website link |
| logo_url | string | Logo image URL |
| social_links | json | Social media links |
| is_verified | boolean | Verified status |
| is_active | boolean | Active status |
| deleted_at | timestamp | Soft delete |

#### `cfm_publisher_content`
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| publisher_id | bigint | FK to cfm_publishers |
| cfm_week_id | bigint | FK to cfm_weeks |
| title | string | Content title |
| content_type | enum | video, podcast, blog, pdf, other |
| external_url | string | Link to content |
| description | text | Content description |
| thumbnail_url | string | Thumbnail image |
| duration_seconds | int | Duration for video/podcast |
| is_featured | boolean | Featured flag |

## Indexes

Key indexes for performance:

- `scripture_chapters`: (`book_id`, `chapter_number`)
- `cfm_weeks`: (`start_date`, `end_date`), `slug`
- `cfm_week_chapters`: `chapter_id` (for resurfacing queries)
- `post_scripture_references`: (`start_chapter_id`, `start_verse`)
- `cfm_publisher_content`: (`cfm_week_id`, `content_type`)
