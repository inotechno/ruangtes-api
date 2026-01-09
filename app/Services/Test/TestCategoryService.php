<?php

namespace App\Services\Test;

use App\Models\TestCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class TestCategoryService
{
    /**
     * Get all test categories with pagination.
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = TestCategory::query();

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $sortBy = $filters['sort_by'] ?? 'sort_order';
        $sortOrder = $filters['sort_order'] ?? 'asc';

        return $query->orderBy($sortBy, $sortOrder)
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get test category by ID.
     */
    public function getById(int $id): TestCategory
    {
        return TestCategory::findOrFail($id);
    }

    /**
     * Create new test category.
     */
    public function create(array $data): TestCategory
    {
        $data['slug'] = Str::slug($data['name']);

        return TestCategory::create($data);
    }

    /**
     * Update test category.
     */
    public function update(TestCategory $category, array $data): TestCategory
    {
        if (isset($data['name']) && $data['name'] !== $category->name) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category->update($data);

        return $category->fresh();
    }

    /**
     * Delete test category.
     */
    public function delete(TestCategory $category): bool
    {
        return $category->delete();
    }
}
