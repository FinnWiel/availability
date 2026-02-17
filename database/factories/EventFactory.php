<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->sentence(2),
            'color' => fake()->randomElement([
                '#2563EB',
                '#16A34A',
                '#DC2626',
                '#9333EA',
                '#D97706',
            ]),
        ];
    }
}
