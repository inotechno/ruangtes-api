<?php

namespace App\Services\Company;

use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CompanyService
{
    /**
     * Get all companies with pagination.
     */
    public function getAll(array $filters = []): LengthAwarePaginator|Collection
    {
        $query = Company::query()
            ->withCount(['admins', 'participants', 'subscriptions'])
            ->with(['activeSubscription']);

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['has_subscription'])) {
            if ($filters['has_subscription']) {
                $query->has('subscriptions');
            } else {
                $query->doesntHave('subscriptions');
            }
        }

        if (isset($filters['has_active_subscription'])) {
            if ($filters['has_active_subscription']) {
                $query->whereHas('subscriptions', function ($q) {
                    $q->where('status', 'active')
                        ->where('expires_at', '>', now());
                });
            } else {
                $query->whereDoesntHave('subscriptions', function ($q) {
                    $q->where('status', 'active')
                        ->where('expires_at', '>', now());
                });
            }
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        $query->orderBy($sortBy, $sortOrder);

        if (isset($filters['per_page']) && $filters['per_page'] === 'all') {
            return $query->get();
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get company by ID.
     */
    public function getById(int $id): Company
    {
        return Company::with(['admins.user', 'participants', 'subscriptions'])->findOrFail($id);
    }

    /**
     * Create new company.
     */
    public function create(array $data): Company
    {
        return Company::create($data);
    }

    /**
     * Update company.
     */
    public function update(Company $company, array $data): Company
    {
        $company->update($data);

        return $company->fresh()->load(['admins.user', 'participants', 'subscriptions']);
    }

    /**
     * Delete company.
     */
    public function delete(Company $company): bool
    {
        return $company->delete();
    }

    /**
     * Toggle company active status.
     */
    public function toggleActive(Company $company): Company
    {
        $company->update(['is_active' => ! $company->is_active]);

        return $company->fresh();
    }
}
