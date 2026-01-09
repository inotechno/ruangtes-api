<?php

namespace App\Services\Test;

use App\Models\Test;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TestService
{
    /**
     * Get all tests with pagination.
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = Test::with('category');

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $sortBy = $filters['sort_by'] ?? 'sort_order';
        $sortOrder = $filters['sort_order'] ?? 'asc';

        return $query->orderBy($sortBy, $sortOrder)
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get test by ID.
     */
    public function getById(int $id): Test
    {
        return Test::with('category')->findOrFail($id);
    }

    /**
     * Create new test.
     */
    public function create(array $data): Test
    {
        return Test::create($data);
    }

    /**
     * Update test.
     */
    public function update(Test $test, array $data): Test
    {
        $test->update($data);

        return $test->fresh()->load('category');
    }

    /**
     * Delete test.
     */
    public function delete(Test $test): bool
    {
        return $test->delete();
    }
}
