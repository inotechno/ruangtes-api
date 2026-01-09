<?php

namespace App\Services\Subscription;

use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPrice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SubscriptionPriceService
{
    /**
     * Get all subscription prices for a plan.
     */
    public function getByPlan(int $planId, array $filters = []): LengthAwarePaginator
    {
        $query = SubscriptionPrice::where('subscription_plan_id', $planId);

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        $sortBy = $filters['sort_by'] ?? 'user_quota';
        $sortOrder = $filters['sort_order'] ?? 'asc';

        return $query->orderBy($sortBy, $sortOrder)
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get subscription price by ID.
     */
    public function getById(int $id): SubscriptionPrice
    {
        return SubscriptionPrice::with('subscriptionPlan')->findOrFail($id);
    }

    /**
     * Create new subscription price.
     */
    public function create(array $data): SubscriptionPrice
    {
        return SubscriptionPrice::create($data);
    }

    /**
     * Update subscription price.
     */
    public function update(SubscriptionPrice $price, array $data): SubscriptionPrice
    {
        $price->update($data);

        return $price->fresh()->load('subscriptionPlan');
    }

    /**
     * Delete subscription price.
     */
    public function delete(SubscriptionPrice $price): bool
    {
        return $price->delete();
    }

    /**
     * Get all prices for a plan (non-paginated, for dropdowns).
     */
    public function getAllForPlan(int $planId): \Illuminate\Database\Eloquent\Collection
    {
        return SubscriptionPrice::where('subscription_plan_id', $planId)
            ->where('is_active', true)
            ->orderBy('user_quota', 'asc')
            ->get();
    }
}
