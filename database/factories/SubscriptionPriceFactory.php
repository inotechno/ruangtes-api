<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SubscriptionPrice>
 */
class SubscriptionPriceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subscription_plan_id' => \App\Models\SubscriptionPlan::factory(),
            'user_quota' => fake()->randomElement([5, 10, 30, 60, 100, 0]),
            'price' => fake()->randomFloat(2, 500000, 10000000),
            'price_per_additional_user' => fake()->randomFloat(2, 40000, 150000),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function custom(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_quota' => 0,
            'price' => 0,
        ]);
    }
}
