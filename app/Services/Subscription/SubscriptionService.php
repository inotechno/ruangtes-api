<?php

namespace App\Services\Subscription;

use App\Enums\SubscriptionStatus;
use App\Enums\TransactionStatus;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPrice;
use App\Models\SubscriptionTransaction;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function purchasePrePaid(
        Company $company,
        SubscriptionPlan $plan,
        SubscriptionPrice $price,
        ?int $additionalUsers = 0
    ): CompanySubscription {
        return DB::transaction(function () use ($company, $plan, $price, $additionalUsers) {
            // Calculate total amount
            $baseAmount = $price->price;
            $additionalAmount = $additionalUsers > 0
                ? $additionalUsers * $price->price_per_additional_user
                : 0;
            $totalAmount = $baseAmount + $additionalAmount;

            // Calculate user quota
            $userQuota = $price->user_quota + $additionalUsers;

            // Calculate dates
            $startedAt = now();
            $expiresAt = $startedAt->copy()->addMonths($plan->duration_months);

            // Create subscription with pending status
            $subscription = CompanySubscription::create([
                'company_id' => $company->id,
                'subscription_plan_id' => $plan->id,
                'subscription_price_id' => $price->id,
                'user_quota' => $userQuota,
                'used_quota' => 0,
                'status' => SubscriptionStatus::Pending,
                'billing_type' => 'pre_paid',
                'started_at' => $startedAt,
                'expires_at' => $expiresAt,
            ]);

            // Create transaction
            $transaction = SubscriptionTransaction::create([
                'company_subscription_id' => $subscription->id,
                'transaction_number' => $this->generateTransactionNumber(),
                'amount' => $totalAmount,
                'status' => TransactionStatus::Pending,
                'metadata' => [
                    'additional_users' => $additionalUsers,
                    'base_amount' => $baseAmount,
                    'additional_amount' => $additionalAmount,
                ],
            ]);

            return $subscription->load(['subscriptionPlan', 'subscriptionPrice', 'transactions']);
        });
    }

    public function purchasePostPaid(
        Company $company,
        SubscriptionPlan $plan,
        SubscriptionPrice $price,
        ?int $additionalUsers = 0
    ): CompanySubscription {
        return DB::transaction(function () use ($company, $plan, $price, $additionalUsers) {
            // Calculate user quota
            $userQuota = $price->user_quota + $additionalUsers;

            // Calculate dates
            $startedAt = now();
            $expiresAt = $startedAt->copy()->addMonths($plan->duration_months);

            // Create subscription with active status (post-paid activates immediately)
            $subscription = CompanySubscription::create([
                'company_id' => $company->id,
                'subscription_plan_id' => $plan->id,
                'subscription_price_id' => $price->id,
                'user_quota' => $userQuota,
                'used_quota' => 0,
                'status' => SubscriptionStatus::Active,
                'billing_type' => 'post_paid',
                'started_at' => $startedAt,
                'expires_at' => $expiresAt,
            ]);

            // Invoice will be generated at the end of period by scheduled job
            // For now, we just create the subscription

            return $subscription->load(['subscriptionPlan', 'subscriptionPrice']);
        });
    }

    public function activateSubscription(CompanySubscription $subscription): CompanySubscription
    {
        $subscription->update([
            'status' => SubscriptionStatus::Active,
            'started_at' => now(),
        ]);

        // Update transaction status
        $transaction = $subscription->transactions()->latest()->first();
        if ($transaction instanceof SubscriptionTransaction && $transaction->isPending()) {
            $transaction->update([
                'status' => TransactionStatus::Completed,
            ]);
        }

        return $subscription->fresh(['subscriptionPlan', 'subscriptionPrice']);
    }

    public function purchaseUserQuota(
        CompanySubscription $subscription,
        int $additionalUsers
    ): SubscriptionTransaction {
        if (! $subscription->isActive()) {
            throw new \Exception('Subscription must be active to purchase additional quota.');
        }

        $price = $subscription->subscriptionPrice;
        $amount = $additionalUsers * $price->price_per_additional_user;

        return DB::transaction(function () use ($subscription, $additionalUsers, $amount) {
            // Update subscription quota
            $subscription->increment('user_quota', $additionalUsers);

            // Create transaction
            $transaction = SubscriptionTransaction::create([
                'company_subscription_id' => $subscription->id,
                'transaction_number' => $this->generateTransactionNumber(),
                'amount' => $amount,
                'status' => TransactionStatus::Pending,
                'metadata' => [
                    'type' => 'quota_purchase',
                    'additional_users' => $additionalUsers,
                ],
            ]);

            return $transaction;
        });
    }

    public function extendSubscription(
        CompanySubscription $subscription,
        int $months
    ): CompanySubscription {
        if (! $subscription->isActive()) {
            throw new \Exception('Only active subscriptions can be extended.');
        }

        $newExpiresAt = $subscription->expires_at->copy()->addMonths($months);

        $subscription->update([
            'expires_at' => $newExpiresAt,
        ]);

        return $subscription->fresh(['subscriptionPlan', 'subscriptionPrice']);
    }

    public function cancelSubscription(CompanySubscription $subscription): CompanySubscription
    {
        $subscription->update([
            'status' => SubscriptionStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        return $subscription->fresh();
    }

    public function expireSubscription(CompanySubscription $subscription): CompanySubscription
    {
        $subscription->update([
            'status' => SubscriptionStatus::Expired,
        ]);

        return $subscription->fresh();
    }

    public function getAvailablePlans(): \Illuminate\Database\Eloquent\Collection
    {
        return SubscriptionPlan::where('is_active', true)
            ->with(['activePrices'])
            ->orderBy('sort_order')
            ->get();
    }

    public function getActiveSubscriptionForCompany(Company $company): ?CompanySubscription
    {
        return $company->activeSubscription();
    }

    public function getSubscriptionHistoryForCompany(Company $company, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $company->subscriptions()
            ->with(['subscriptionPlan', 'subscriptionPrice', 'transactions.payment', 'invoices.payments'])
            ->latest()
            ->paginate($perPage);
    }

    public function verifySubscriptionBelongsToCompany(CompanySubscription $subscription, Company $company): bool
    {
        return $subscription->company_id === $company->id;
    }

    protected function generateTransactionNumber(): string
    {
        do {
            $number = 'TXN-'.date('Ymd').'-'.strtoupper(substr(uniqid(), -8));
        } while (SubscriptionTransaction::where('transaction_number', $number)->exists());

        return $number;
    }
}
