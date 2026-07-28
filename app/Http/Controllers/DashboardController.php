<?php

namespace App\Http\Controllers;

use App\Models\FriendInvitation;
use App\Models\Lesson;
use App\Models\Post;
use App\Models\StudyPlan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $myPosts = Post::where('user_id', $user->id)
            ->with(['category', 'tags'])
            ->latest()
            ->paginate(10);

        $friendPosts = Post::with(['user', 'category', 'tags'])
            ->whereIn('user_id', $user->friendIds())
            ->where('visibility', '!=', 'private')
            ->published()
            ->latest('published_at')
            ->limit(5)
            ->get();

        $myLessons = Lesson::where('user_id', $user->id)
            ->with('cfmWeek')
            ->latest()
            ->limit(5)
            ->get();

        $pendingFriendRequests = $user->pendingFriendRequests()
            ->with('requester')
            ->count();

        $userCategories = $user->userCategories()
            ->root()
            ->withCount('children')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Dashboard', [
            'myPosts' => $myPosts,
            'myLessons' => $myLessons,
            'friendPosts' => $friendPosts,
            'pendingFriendRequestsCount' => $pendingFriendRequests,
            'userCategories' => $userCategories,
            'gettingStarted' => $this->gettingStartedSteps($user, $myPosts->total(), $myLessons->isNotEmpty()),
        ]);
    }

    /**
     * New-user checklist steps with completion derived from real data.
     * Null once dismissed. Visit-based steps are flagged by the Library
     * and Lessons index pages.
     */
    protected function gettingStartedSteps($user, int $postCount, bool $hasLessons): ?array
    {
        if ($user->getSetting('getting_started_dismissed', false)) {
            return null;
        }

        $steps = [
            [
                'label' => 'Write your first post',
                'description' => 'Share a story, quote, or thought — publicly, with friends, or just for yourself.',
                'href' => route('posts.create'),
                'done' => $postCount > 0,
            ],
        ];

        if ($user->show_lds_content) {
            $steps[] = [
                'label' => 'Browse the talk Library',
                'description' => 'Over 4,000 General Conference talks with excerpts, filters, and topic tags.',
                'href' => route('talks.index'),
                'done' => (bool) $user->getSetting('visited_library', false),
            ];
        }

        $steps[] = [
            'label' => 'Create a study plan',
            'description' => 'Pick scriptures or talks, set your pace, and track your reading.',
            'href' => route('study-plans.create'),
            'done' => StudyPlan::where('user_id', $user->id)->exists(),
        ];

        $steps[] = [
            'label' => 'Explore Lessons',
            'description' => 'See how lessons are built from scriptures, talks, and quotes — then teach from the app.',
            'href' => route('lessons.index'),
            'done' => $hasLessons || (bool) $user->getSetting('visited_lessons', false),
        ];

        $steps[] = [
            'label' => 'Invite a friend',
            'description' => 'Friends see the posts you share with them — and you see theirs.',
            'href' => route('friends.index'),
            'done' => ! empty($user->friendIds())
                || FriendInvitation::where('inviter_id', $user->id)->exists(),
        ];

        return $steps;
    }
}
