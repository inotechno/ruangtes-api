<?php

namespace App\Services\User;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
class UserService
{
    public function getAllUsers(array $filters = []): LengthAwarePaginator
    {
        
        $query = User::with('userable');

        if (isset($filters['role'])) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->where('name', UserRole::from($filters['role']));
            });
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        return $query->orderBy($sortBy, $sortOrder)
            ->paginate($filters['per_page'] ?? 15);
    }

    public function getUserById(int $id): User
    {
        return User::with('userable', 'roles', 'permissions')->findOrFail($id);
    }

    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create($data);
            $user->assignRole(UserRole::from($data['role']));
            $user->userable()->create($data['userable']);
            return $user->load('userable', 'roles', 'permissions');
        });
    }

    public function updateUser(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $user->update($data);
            $user->assignRole(UserRole::from($data['role']));
            $user->userable()->update($data['userable']);
            return $user->load('userable', 'roles', 'permissions');
        });
    }

    public function deleteUser(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            $user->delete();
            $user->userable()->delete();
            $user->roles()->detach();
            $user->permissions()->detach();
            return true;
        });
    }
}