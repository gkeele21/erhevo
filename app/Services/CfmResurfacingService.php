<?php

namespace App\Services;

use App\Models\CfmWeek;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CfmResurfacingService
{
    /**
     * Get posts that should resurface for a given CFM week.
     *
     * Posts resurface when their scripture references overlap with the week's chapters.
     * This allows content created in one study cycle (e.g., 2024) to appear when the
     * same scriptures are studied in the next cycle (e.g., 2028).
     */
    public function getResurfacingPosts(CfmWeek $currentWeek, ?User $viewer = null): Collection
    {
        // Get all chapter IDs for the current week
        $chapterIds = $currentWeek->chapters()->pluck('scripture_chapters.id');

        if ($chapterIds->isEmpty()) {
            // This is a special topic week with no scripture chapters
            return $this->getSpecialTopicPosts($currentWeek, $viewer);
        }

        return Post::query()
            ->published()
            ->visibleTo($viewer)
            ->where(function ($query) use ($chapterIds) {
                // Posts whose scripture references overlap with these chapters
                $query->whereHas('scriptureReferences', function ($q) use ($chapterIds) {
                    $q->where(function ($subQuery) use ($chapterIds) {
                        // Start chapter is in the week's chapters
                        $subQuery->whereIn('start_chapter_id', $chapterIds);
                    })->orWhere(function ($subQuery) use ($chapterIds) {
                        // End chapter is in the week's chapters (for multi-chapter ranges)
                        $subQuery->whereIn('end_chapter_id', $chapterIds);
                    });
                });
            })
            ->with([
                'user',
                'scriptureReferences.startChapter.book.volume',
                'scriptureReferences.endChapter',
            ])
            ->orderByDesc('published_at')
            ->get();
    }

    /**
     * Get posts for special topic weeks (Christmas, Easter, General Conference).
     *
     * Posts linked to any week with the same special topic will resurface.
     */
    public function getSpecialTopicPosts(CfmWeek $currentWeek, ?User $viewer = null): Collection
    {
        $topicIds = $currentWeek->specialTopics()->pluck('cfm_special_topics.id');

        if ($topicIds->isEmpty()) {
            return new Collection();
        }

        // Find all weeks with the same special topics
        $relatedWeekIds = DB::table('cfm_week_topics')
            ->whereIn('special_topic_id', $topicIds)
            ->pluck('cfm_week_id');

        return Post::query()
            ->whereHas('cfmWeeks', function ($q) use ($relatedWeekIds) {
                $q->whereIn('cfm_weeks.id', $relatedWeekIds);
            })
            ->published()
            ->visibleTo($viewer)
            ->with(['user', 'cfmWeeks.studyYear'])
            ->orderByDesc('published_at')
            ->get();
    }

    /**
     * Get all posts for a specific CFM week, combining both types.
     */
    public function getAllPostsForWeek(CfmWeek $week, ?User $viewer = null): Collection
    {
        $scripturePosts = $this->getResurfacingPosts($week, $viewer);
        $topicPosts = $this->getSpecialTopicPosts($week, $viewer);

        // Merge and deduplicate by ID
        return $scripturePosts->merge($topicPosts)
            ->unique('id')
            ->sortByDesc('published_at')
            ->values();
    }

    /**
     * Get the current CFM week based on today's date.
     */
    public function getCurrentWeek(): ?CfmWeek
    {
        $today = now()->toDateString();

        return CfmWeek::where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->with(['studyYear', 'chapters.book', 'specialTopics'])
            ->first();
    }

    /**
     * Get user's posts that will resurface in future CFM weeks.
     *
     * Useful for showing users when their content will appear again.
     */
    public function getUserResurfacingForecast(User $user, int $yearsAhead = 4): array
    {
        $forecast = [];

        // Get all the user's posts with scripture references
        $posts = $user->posts()
            ->published()
            ->whereHas('scriptureReferences')
            ->with('scriptureReferences.startChapter.book')
            ->get();

        foreach ($posts as $post) {
            $chapterIds = $post->scriptureReferences
                ->flatMap(fn($ref) => $ref->getCoveredChapterIds())
                ->unique();

            // Find future weeks that cover these chapters
            $futureWeeks = CfmWeek::query()
                ->whereHas('chapters', function ($q) use ($chapterIds) {
                    $q->whereIn('scripture_chapters.id', $chapterIds);
                })
                ->where('start_date', '>', now())
                ->where('start_date', '<=', now()->addYears($yearsAhead))
                ->with('studyYear')
                ->orderBy('start_date')
                ->get();

            if ($futureWeeks->isNotEmpty()) {
                $forecast[] = [
                    'post' => $post,
                    'weeks' => $futureWeeks,
                ];
            }
        }

        return $forecast;
    }
}
