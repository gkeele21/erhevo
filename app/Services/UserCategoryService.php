<?php

namespace App\Services;

use App\Models\DefaultCategory;
use App\Models\User;
use App\Models\UserCategory;

class UserCategoryService
{
    public function copyDefaultsToUser(User $user): void
    {
        $rootCategories = DefaultCategory::root()->orderBy('sort_order')->get();

        foreach ($rootCategories as $rootCategory) {
            $userCategory = UserCategory::create([
                'user_id' => $user->id,
                'name' => $rootCategory->name,
                'slug' => $rootCategory->slug,
                'description' => $rootCategory->description,
                'sort_order' => $rootCategory->sort_order,
            ]);

            foreach ($rootCategory->children as $childCategory) {
                UserCategory::create([
                    'user_id' => $user->id,
                    'name' => $childCategory->name,
                    'slug' => $childCategory->slug,
                    'description' => $childCategory->description,
                    'parent_id' => $userCategory->id,
                    'sort_order' => $childCategory->sort_order,
                ]);
            }
        }
    }
}
