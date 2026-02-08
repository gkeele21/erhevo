# CFM Publishers

## Overview

The publisher system allows tracking of external CFM content creators and linking to their resources (videos, podcasts, blogs) for each study week.

## No Copyrighted Content

We store only metadata and links, never the actual content:
- Publisher name and description
- Links to their website/social media
- Links to specific content for each week
- Thumbnails (via URL, not storage)

This approach:
- Avoids copyright issues
- Keeps content fresh (always links to the source)
- Drives traffic to creators

## Publisher Model

```php
$publisher = CfmPublisher::create([
    'name' => 'Don\'t Miss This',
    'slug' => 'dont-miss-this',
    'description' => 'Weekly CFM study videos by Emily Belle Freeman and David Butler',
    'website_url' => 'https://dontmissthis.tv',
    'social_links' => [
        'youtube' => 'https://youtube.com/@DontMissThis',
        'instagram' => 'https://instagram.com/dontmissthis',
    ],
    'is_verified' => true,
    'is_active' => true,
]);
```

## Publisher Content

```php
$content = CfmPublisherContent::create([
    'publisher_id' => $publisher->id,
    'cfm_week_id' => $week->id,
    'title' => '1 Nephi 1-7 | Don\'t Miss This',
    'content_type' => CfmContentType::Video,
    'external_url' => 'https://youtube.com/watch?v=...',
    'description' => 'Study 1 Nephi 1-7 with Emily and David',
    'thumbnail_url' => 'https://i.ytimg.com/vi/.../hqdefault.jpg',
    'duration_seconds' => 2340, // 39 minutes
    'is_featured' => true,
]);
```

## Content Types

The `CfmContentType` enum supports:

| Type | Icon | Description |
|------|------|-------------|
| `video` | play-circle | YouTube, Vimeo videos |
| `podcast` | microphone | Audio podcast episodes |
| `blog` | document-text | Written articles |
| `pdf` | document | Downloadable PDFs |
| `other` | link | Other resources |

## Popular LDS CFM Publishers

Some well-known CFM content creators:
- Don't Miss This (Emily Belle Freeman & David Butler)
- Scripture Central / Book of Mormon Central
- Latter-day Saints Channel
- BYU Religious Education
- Living Scriptures
- Various individual bloggers and podcasters

## Querying Content

### Get all content for a week

```php
$week->publisherContent()
    ->with('publisher')
    ->orderByDesc('is_featured')
    ->get();
```

### Get featured content only

```php
$week->publisherContent()
    ->featured()
    ->with('publisher')
    ->get();
```

### Get all content from a publisher

```php
$publisher->content()
    ->with('cfmWeek.studyYear')
    ->orderByDesc('created_at')
    ->get();
```

## Verification

The `is_verified` flag indicates official/trusted publishers. This can be used to:
- Show a verification badge
- Prioritize in search results
- Filter to verified sources only

## Management

Publishers and content would typically be managed by admins. Consider:
- Admin dashboard for publisher CRUD
- Content submission by verified publishers
- Moderation queue for new publishers
