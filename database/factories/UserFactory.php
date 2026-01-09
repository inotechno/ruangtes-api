<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create a user as CompanyAdmin.
     */
    public function asCompanyAdmin(): static
    {
        return $this->afterCreating(function ($user) {
            $company = \App\Models\Company::factory()->create();
            $companyAdmin = \App\Models\CompanyAdmin::factory()->create([
                'user_id' => $user->id,
                'company_id' => $company->id,
            ]);
            $user->update([
                'userable_type' => \App\Models\CompanyAdmin::class,
                'userable_id' => $companyAdmin->id,
            ]);
            $user->assignRole('tenant_admin');
        });
    }

    /**
     * Create a user as PublicUser.
     */
    public function asPublicUser(): static
    {
        return $this->afterCreating(function ($user) {
            $publicUser = \App\Models\PublicUser::factory()->create([
                'user_id' => $user->id,
            ]);
            $user->update([
                'userable_type' => \App\Models\PublicUser::class,
                'userable_id' => $publicUser->id,
            ]);
            $user->assignRole('public_user');
        });
    }

    /**
     * Create a user as SuperAdmin.
     */
    public function asSuperAdmin(): static
    {
        return $this->afterCreating(function ($user) {
            $user->assignRole('super_admin');
        });
    }
}
