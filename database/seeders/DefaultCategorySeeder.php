<?php

namespace Database\Seeders;

use App\Models\DefaultCategory;
use Illuminate\Database\Seeder;

class DefaultCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Personal',
                'slug' => 'personal',
                'description' => 'Personal life experiences and reflections',
                'sort_order' => 1,
                'children' => [
                    ['name' => 'Family', 'slug' => 'family', 'description' => 'Family moments and memories', 'sort_order' => 1],
                    ['name' => 'Health', 'slug' => 'health', 'description' => 'Health and wellness', 'sort_order' => 2],
                    ['name' => 'Goals', 'slug' => 'goals', 'description' => 'Personal goals and achievements', 'sort_order' => 3],
                ],
            ],
            [
                'name' => 'Work',
                'slug' => 'work',
                'description' => 'Professional and career related',
                'sort_order' => 2,
                'children' => [
                    ['name' => 'Projects', 'slug' => 'projects', 'description' => 'Work projects and accomplishments', 'sort_order' => 1],
                    ['name' => 'Meetings', 'slug' => 'meetings', 'description' => 'Notable meetings and discussions', 'sort_order' => 2],
                ],
            ],
            [
                'name' => 'Spiritual',
                'slug' => 'spiritual',
                'description' => 'Spiritual growth and insights',
                'sort_order' => 3,
                'children' => [
                    ['name' => 'Scripture Study', 'slug' => 'scripture-study', 'description' => 'Scripture study notes and insights', 'sort_order' => 1],
                    ['name' => 'Insights', 'slug' => 'insights', 'description' => 'Spiritual insights and promptings', 'sort_order' => 2],
                ],
            ],
        ];

        foreach ($categories as $categoryData) {
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);

            $parent = DefaultCategory::create($categoryData);

            foreach ($children as $childData) {
                DefaultCategory::create([
                    ...$childData,
                    'parent_id' => $parent->id,
                ]);
            }
        }
    }
}
