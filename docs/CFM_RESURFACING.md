# Content Resurfacing

## Overview

The resurfacing mechanism allows posts created for a specific scripture study to reappear when those same scriptures are studied again (typically 4 years later in the CFM rotation).

## How It Works

### For Scripture-Based Weeks

1. User creates a post about "1 Nephi 3:7" in 2024
2. The post is linked via `post_scripture_references` to chapter ID for 1 Nephi 3
3. In 2028, when viewing the CFM week covering 1 Nephi 3:
   - The system queries for posts whose scripture references overlap
   - The 2024 post appears alongside 2028 content

### For Special Topics

1. User creates a post linked to the "Christmas" week in 2024
2. The post is linked via `post_cfm_weeks` to that week
3. The week is linked via `cfm_week_topics` to the "Christmas" special topic
4. In 2025's Christmas week:
   - The system finds all weeks with the same special topic
   - Posts from any Christmas week appear

## Query Pattern

### Scripture-Based Resurfacing

```php
public function getResurfacingPosts(CfmWeek $currentWeek, ?User $user): Collection
{
    // Get all chapter IDs for current week
    $chapterIds = $currentWeek->chapters()->pluck('scripture_chapters.id');

    return Post::query()
        ->visibleTo($user)
        ->published()
        ->where(function ($query) use ($chapterIds) {
            // Posts with scripture references to these chapters
            $query->whereHas('scriptureReferences', function ($q) use ($chapterIds) {
                $q->whereIn('start_chapter_id', $chapterIds)
                  ->orWhereIn('end_chapter_id', $chapterIds);
            });
        })
        ->with(['user', 'scriptureReferences.startChapter.book'])
        ->latest('published_at')
        ->get();
}
```

### Special Topic Resurfacing

```php
public function getSpecialTopicPosts(CfmWeek $currentWeek, ?User $user): Collection
{
    $topicIds = $currentWeek->specialTopics()->pluck('cfm_special_topics.id');

    if ($topicIds->isEmpty()) {
        return collect();
    }

    // Find all weeks with the same special topics
    $relatedWeekIds = DB::table('cfm_week_topics')
        ->whereIn('special_topic_id', $topicIds)
        ->pluck('cfm_week_id');

    return Post::query()
        ->whereHas('cfmWeeks', function ($q) use ($relatedWeekIds) {
            $q->whereIn('cfm_weeks.id', $relatedWeekIds);
        })
        ->visibleTo($user)
        ->published()
        ->get();
}
```

## Privacy Considerations

Resurfacing always respects post visibility:

- **Public posts**: Visible to everyone
- **Private posts**: Only visible to the creator
- **Friends-only posts**: Only visible to the creator's friends

The `visibleTo()` scope handles this automatically.

## Performance

Key indexes support efficient resurfacing queries:

- `cfm_week_chapters.chapter_id` - For finding weeks covering a chapter
- `post_scripture_references.start_chapter_id` - For finding posts about a chapter
- Composite indexes on visibility and published_at

## The 4-Year Cycle

Because CFM follows a 4-year rotation:
- Book of Mormon → D&C → OT/PGP → NT → repeat

Posts about specific scriptures will resurface every 4 years when those scriptures are studied again.
