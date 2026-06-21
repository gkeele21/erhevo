<?php

namespace Database\Factories;

use App\Enums\Visibility;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lesson>
 */
class LessonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'cfm_week_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->sentence(),
            'visibility' => Visibility::Private,
            'published_at' => now(),
        ];
    }

    /**
     * Indicate that the lesson is an unpublished draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => null,
        ]);
    }

    /**
     * Indicate that the lesson is publicly visible.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => Visibility::Public,
        ]);
    }
}
