<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'shareUrl' => fn () => $request->session()->get('shareUrl'),
            ],
            'isAdmin' => fn () => $request->user()?->isAdmin() ?? false,
            // Items needing attention, shown in the nav bell: pending friend
            // requests and shared study plans not yet opened. Each clears by
            // its own action (accept/decline; open the plan).
            'notifications' => fn () => $this->notifications($request),
            'userSettings' => fn () => $request->user() ? [
                'show_lds_content' => $request->user()->show_lds_content,
            ] : null,
            'ai' => fn () => [
                'connected' => $request->user()?->hasAiConnection() ?? false,
                'provider' => $request->user()?->ai_provider,
                // key_preview: just enough of the stored key ("sk-") to make
                // "you already have a key here" visually obvious.
                'connections' => $request->user()?->aiConnections->map(fn ($c) => [
                    'provider' => $c->provider,
                    'key_preview' => substr($c->api_key, 0, 3),
                ])->values() ?? [],
                'providers' => app(\App\AI\AiManager::class)->providerOptions(),
            ],
        ];
    }

    /** @return array{count: int, items: array} */
    protected function notifications(Request $request): array
    {
        $user = $request->user();

        if (! $user) {
            return ['count' => 0, 'items' => []];
        }

        $items = [];

        foreach ($user->pendingFriendRequests()->with('requester')->latest()->get() as $friendRequest) {
            $items[] = [
                'type' => 'friend_request',
                'label' => ($friendRequest->requester?->name ?? 'Someone') . ' sent you a friend request',
                'href' => route('friends.index'),
            ];
        }

        $unseenPlans = \App\Models\StudyPlan::whereHas('members', fn ($q) => $q
                ->where('study_plan_members.user_id', $user->id)
                ->whereNull('study_plan_members.seen_at'))
            ->with('user:id,first_name,last_name')
            ->latest()
            ->get();

        foreach ($unseenPlans as $plan) {
            $items[] = [
                'type' => 'shared_plan',
                'label' => ($plan->user?->name ?? 'A friend') . ' shared “' . $plan->name . '” with you',
                'href' => route('study-plans.show', $plan->id),
            ];
        }

        return ['count' => count($items), 'items' => $items];
    }
}
