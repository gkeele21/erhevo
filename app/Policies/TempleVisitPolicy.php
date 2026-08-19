<?php

namespace App\Policies;

use App\Models\TempleVisit;
use App\Models\User;

class TempleVisitPolicy
{
    public function update(User $user, TempleVisit $visit): bool
    {
        return $user->id === $visit->user_id;
    }

    public function delete(User $user, TempleVisit $visit): bool
    {
        return $user->id === $visit->user_id;
    }
}
