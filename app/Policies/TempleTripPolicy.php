<?php

namespace App\Policies;

use App\Models\TempleTrip;
use App\Models\User;

class TempleTripPolicy
{
    public function view(User $user, TempleTrip $trip): bool
    {
        return $user->id === $trip->user_id;
    }

    public function update(User $user, TempleTrip $trip): bool
    {
        return $user->id === $trip->user_id;
    }

    public function delete(User $user, TempleTrip $trip): bool
    {
        return $user->id === $trip->user_id;
    }
}
