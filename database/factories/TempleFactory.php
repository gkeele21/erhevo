<?php

namespace Database\Factories;

use App\Models\Temple;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Temple>
 */
class TempleFactory extends Factory
{
    public function definition(): array
    {
        $city = fake()->city();

        return [
            'slug' => fake()->unique()->slug(3),
            'name' => "{$city} Temple",
            'address' => fake()->streetAddress(),
            'city' => $city,
            'state' => 'Utah',
            'country' => 'United States',
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'photo_url' => null,
            'dedicated_on' => fake()->date(),
            'source_url' => 'https://example.com/'.fake()->unique()->slug(2).'/',
        ];
    }
}
