<?php

namespace App\Services\Test;

use App\Enums\TestType;
use App\Models\Test;
use App\Models\TestCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TestCatalogService
{
    /**
     * Get available tests for public users.
     */
    public function getAvailableTests(
        ?int $categoryId = null,
        ?string $search = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = Test::with(['category'])
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('type', TestType::Public)
                    ->orWhere('type', TestType::All);
            });

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('code', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        return $query->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Get test details by ID.
     */
    public function getTestDetails(int $testId): ?Test
    {
        $test = Test::with(['category'])
            ->where('id', $testId)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('type', TestType::Public)
                    ->orWhere('type', TestType::All);
            })
            ->first();

        return $test;
    }

    /**
     * Get test categories for filtering.
     */
    public function getCategories(): \Illuminate\Database\Eloquent\Collection
    {
        return TestCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Check if test is available for purchase.
     */
    public function isTestAvailable(Test $test): bool
    {
        return $test->is_active
            && ($test->type === TestType::Public || $test->type === TestType::All);
    }
}
