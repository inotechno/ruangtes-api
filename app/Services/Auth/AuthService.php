<?php

namespace App\Services\Auth;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\CompanyAdmin;
use App\Models\PublicUser;
use App\Models\User;
use App\Services\Auth\EmailVerificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    public function __construct(
        protected EmailVerificationService $emailVerificationService
    ) {
    }

    public function registerPublicUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // Create public user
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'userable_type' => PublicUser::class,
            ]);

            // Create public user record
            $publicUser = PublicUser::create([
                'user_id' => $user->id,
                'phone' => $data['phone'] ?? null,  
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'address' => $data['address'] ?? null,
                'biodata' => $data['biodata'] ?? null,
            ]);

            // Set userable_id
            $user->update(['userable_id' => $publicUser->id]);

            // Assign public_user role
            $user->assignRole(UserRole::PublicUser);

            // Send email verification
            $this->emailVerificationService->sendVerificationEmail($user);

            return $user->load('userable');
        });
    }

    public function registerCompanyWithAdmin(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // Create company
            $company = Company::create([
                'name' => $data['company_name'],
                'email' => $data['company_email'],
                'phone' => $data['company_phone'] ?? null,
                'address' => $data['company_address'] ?? null,
                'is_active' => false, // Will be activated after email verification
            ]);

            // Create admin user
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'userable_type' => CompanyAdmin::class,
            ]);

            // Create company admin record
            $companyAdmin = CompanyAdmin::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'is_primary' => true,
            ]);

            // Set userable_id
            $user->update(['userable_id' => $companyAdmin->id]);

            // Assign tenant_admin role
            $user->assignRole(UserRole::TenantAdmin);

            // Send email verification
            $this->emailVerificationService->sendVerificationEmail($user);

            return $user->load('userable');
        });
    }

    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw new \Exception('Invalid credentials');
        }

        if (! $user->email_verified_at) {
            throw new \Exception('Email not verified');
        }

        // Revoke existing tokens
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user' => $user->load(['userable', 'roles', 'permissions']),
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}
