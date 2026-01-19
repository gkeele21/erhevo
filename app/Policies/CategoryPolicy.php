<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Category $category): bool
    {
        return $category->is_approved || ($user && $user->id === $category->user_id);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Category $category): bool
    {
        // Only admin or creator (if not approved) can update
        if ($category->isAdminCreated()) {
            return $this->isAdmin($user);
        }

        return $user->id === $category->user_id && ! $category->is_approved;
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->isAdmin($user);
    }

    public function approve(User $user, Category $category): bool
    {
        return $this->isAdmin($user);
    }

    protected function isAdmin(User $user): bool
    {
        // You can customize this based on your admin implementation
        // For now, check if user has 'admin' in their email or a specific role
        return str_contains($user->email, 'admin@');
    }
}
