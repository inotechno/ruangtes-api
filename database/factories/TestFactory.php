<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Test>
 */
class TestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(3, true);
        $code = strtoupper(\Illuminate\Support\Str::random(6));

        return [
            'category_id' => \App\Models\TestCategory::factory(),
            'name' => ucwords($name),
            'code' => $code,
            'price' => fake()->randomFloat(2, 50000, 500000),
            'question_count' => fake()->numberBetween(20, 100),
            'duration_minutes' => fake()->numberBetween(30, 120),
            'type' => \App\Enums\TestType::All,
            'description' => fake()->paragraph(),
            'instruction_route' => 'tests.'.$code.'.instructions',
            'test_route' => 'tests.'.$code.'.take',
            'metadata' => [],
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => \App\Enums\TestType::Public,
        ]);
    }

    public function company(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => \App\Enums\TestType::Company,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
