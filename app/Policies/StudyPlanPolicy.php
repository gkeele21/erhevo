<?php

namespace App\Policies;

use App\Models\StudyPlan;
use App\Models\User;

class StudyPlanPolicy
{
    public function view(User $user, StudyPlan $studyPlan): bool
    {
        return $studyPlan->user_id === $user->id || $studyPlan->isSharedWith($user);
    }

    /** Check readings off — shared with the whole study group. */
    public function participate(User $user, StudyPlan $studyPlan): bool
    {
        return $this->view($user, $studyPlan);
    }

    /** Changing the plan itself (criteria, schedule, members) is owner-only. */
    public function update(User $user, StudyPlan $studyPlan): bool
    {
        return $studyPlan->user_id === $user->id;
    }

    public function delete(User $user, StudyPlan $studyPlan): bool
    {
        return $studyPlan->user_id === $user->id;
    }
}
