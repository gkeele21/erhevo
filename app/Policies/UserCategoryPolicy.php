<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserCategory;

class UserCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, UserCategory $userCategory): bool
    {
        return $user->id === $userCategory->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, UserCategory $userCategory): bool
    {
        return $user->id === $userCategory->user_id;
    }

    public function delete(User $user, UserCategory $userCategory): bool
    {
        return $user->id === $userCategory->user_id;
    }
}
