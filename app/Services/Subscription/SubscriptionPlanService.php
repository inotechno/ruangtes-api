<?php

namespace App\Services\Subscription;

use App\Models\SubscriptionPlan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SubscriptionPlanService
{
    /**
     * Get all subscription plans with pagination.
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = SubscriptionPlan::with('prices');

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['duration_months'])) {
            $query->where('duration_months', $filters['duration_months']);
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
     * Get subscription plan by ID.
     */
    public function getById(int $id): SubscriptionPlan
    {
        return SubscriptionPlan::with('prices')->findOrFail($id);
    }

    /**
     * Create new subscription plan.
     */
    public function create(array $data): SubscriptionPlan
    {
        return SubscriptionPlan::create($data);
    }

    /**
     * Update subscription plan.
     */
    public function update(SubscriptionPlan $plan, array $data): SubscriptionPlan
    {
        $plan->update($data);

        return $plan->fresh()->load('prices');
    }

    /**
     * Delete subscription plan.
     */
    public function delete(SubscriptionPlan $plan): bool
    {
        return $plan->delete();
    }
}
