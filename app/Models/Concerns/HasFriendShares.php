<?php

namespace App\Models\Concerns;

use App\Enums\Visibility;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * For models with "specific friends" (custom) visibility: the chosen friends
 * live in the polymorphic item_shares table.
 */
trait HasFriendShares
{
    public function sharedWith(): MorphToMany
    {
        return $this->morphToMany(User::class, 'shareable', 'item_shares')->withTimestamps();
    }

    public function isSharedWithUser(User $user): bool
    {
        return $this->sharedWith()->whereKey($user->id)->exists();
    }

    /**
     * Store the chosen friends. Only the owner's actual friends are kept, and
     * any non-custom visibility clears the list so stale shares can't linger.
     */
    public function syncSharedWith(array $userIds): void
    {
        if ($this->visibility !== Visibility::Custom) {
            $this->sharedWith()->sync([]);

            return;
        }

        $friendIds = $this->user->friendIds();

        $this->sharedWith()->sync(
            collect($userIds)->filter(fn ($id) => in_array($id, $friendIds))->values()->all()
        );
    }
}
