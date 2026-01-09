<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PublicUser>
 */
class PublicUserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'phone' => fake()->phoneNumber(),
            'date_of_birth' => fake()->date(),
            'address' => fake()->address(),
            'biodata' => [
                'education' => fake()->randomElement(['SMA', 'S1', 'S2', 'S3']),
                'occupation' => fake()->jobTitle(),
            ],
        ];
    }
}
