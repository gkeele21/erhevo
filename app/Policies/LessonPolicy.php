<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\User;

class LessonPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Lesson $lesson): bool
    {
        return $lesson->isVisibleTo($user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Lesson $lesson): bool
    {
        return $user->id === $lesson->user_id;
    }

    public function delete(User $user, Lesson $lesson): bool
    {
        return $user->id === $lesson->user_id;
    }

    public function restore(User $user, Lesson $lesson): bool
    {
        return $user->id === $lesson->user_id;
    }

    public function forceDelete(User $user, Lesson $lesson): bool
    {
        return $user->id === $lesson->user_id;
    }
}
