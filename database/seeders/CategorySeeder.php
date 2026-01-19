<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Inspiration',
                'slug' => 'inspiration',
                'description' => 'Stories that inspire and motivate',
            ],
            [
                'name' => 'Gratitude',
                'slug' => 'gratitude',
                'description' => 'Expressions of thankfulness and appreciation',
            ],
            [
                'name' => 'Kindness',
                'slug' => 'kindness',
                'description' => 'Acts of kindness and compassion',
            ],
            [
                'name' => 'Perseverance',
                'slug' => 'perseverance',
                'description' => 'Stories of overcoming challenges',
            ],
            [
                'name' => 'Love & Family',
                'slug' => 'love-family',
                'description' => 'Stories about love and family bonds',
            ],
            [
                'name' => 'Faith & Hope',
                'slug' => 'faith-hope',
                'description' => 'Messages of faith, hope, and belief',
            ],
            [
                'name' => 'Personal Growth',
                'slug' => 'personal-growth',
                'description' => 'Lessons learned and personal development',
            ],
            [
                'name' => 'Random Acts',
                'slug' => 'random-acts',
                'description' => 'Unexpected moments of goodness',
            ],
        ];

        foreach ($categories as $category) {
            Category::create([
                ...$category,
                'user_id' => null,
                'is_approved' => true,
            ]);
        }
    }
}
