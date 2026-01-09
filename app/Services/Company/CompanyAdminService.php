<?php

namespace App\Services\Company;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\CompanyAdmin;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CompanyAdminService
{
    /**
     * Get all admins for a company.
     */
    public function getByCompany(int $companyId): \Illuminate\Database\Eloquent\Collection
    {
        return CompanyAdmin::where('company_id', $companyId)
            ->with('user')
            ->orderBy('is_primary', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get admin by ID.
     */
    public function getById(int $id): CompanyAdmin
    {
        return CompanyAdmin::with(['user', 'company'])->findOrFail($id);
    }

    /**
     * Create new company admin.
     */
    public function create(int $companyId, array $data): CompanyAdmin
    {
        return DB::transaction(function () use ($companyId, $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'email_verified_at' => now(),
            ]);

            $companyAdmin = CompanyAdmin::create([
                'user_id' => $user->id,
                'company_id' => $companyId,
                'is_primary' => $data['is_primary'] ?? false,
            ]);

            $user->userable_type = CompanyAdmin::class;
            $user->userable_id = $companyAdmin->id;
            $user->save();

            $user->assignRole(UserRole::TENANT_ADMIN->value);

            return $companyAdmin->load('user');
        });
    }

    /**
     * Update admin user information.
     */
    public function update(CompanyAdmin $admin, array $data): CompanyAdmin
    {
        DB::transaction(function () use ($admin, $data) {
            if (isset($data['name'])) {
                $admin->user->update(['name' => $data['name']]);
            }

            if (isset($data['email'])) {
                $admin->user->update(['email' => $data['email']]);
            }

            if (isset($data['password'])) {
                $admin->user->update(['password' => Hash::make($data['password'])]);
            }

            if (isset($data['is_primary'])) {
                $admin->update(['is_primary' => $data['is_primary']]);
            }
        });

        return $admin->fresh()->load('user');
    }

    /**
     * Delete company admin.
     */
    public function delete(CompanyAdmin $admin): bool
    {
        return DB::transaction(function () use ($admin) {
            $user = $admin->user;
            $admin->delete();
            $user->delete();

            return true;
        });
    }

    /**
     * Set admin as primary.
     */
    public function setPrimary(CompanyAdmin $admin): CompanyAdmin
    {
        DB::transaction(function () use ($admin) {
            // Remove primary status from other admins in the same company
            CompanyAdmin::where('company_id', $admin->company_id)
                ->where('id', '!=', $admin->id)
                ->update(['is_primary' => false]);

            // Set this admin as primary
            $admin->update(['is_primary' => true]);
        });

        return $admin->fresh()->load('user');
    }
}
