<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Participant>
 */
class ParticipantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'unique_token' => \Illuminate\Support\Str::random(64),
            'biodata' => [
                'phone' => fake()->phoneNumber(),
                'date_of_birth' => fake()->date(),
                'address' => fake()->address(),
            ],
            'banned_at' => null,
        ];
    }

    public function banned(): static
    {
        return $this->state(fn (array $attributes) => [
            'banned_at' => now(),
        ]);
    }
}
