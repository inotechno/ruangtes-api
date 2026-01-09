<?php

namespace App\Services\Transaction;

use App\Models\PublicTransaction;
use App\Models\PublicTransactionItem;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TransactionService
{
    /**
     * Get transactions for user.
     */
    public function getTransactions(
        User $user,
        ?string $status = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = PublicTransaction::with(['items.test.category', 'payment'])
            ->where('user_id', $user->id);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->latest()
            ->paginate($perPage);
    }

    /**
     * Get transaction details.
     */
    public function getTransaction(User $user, int $transactionId): ?PublicTransaction
    {
        return PublicTransaction::with(['items.test.category', 'payment'])
            ->where('user_id', $user->id)
            ->where('id', $transactionId)
            ->first();
    }

    /**
     * Get purchased tests for user.
     */
    public function getPurchasedTests(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return PublicTransaction::with(['items.test.category'])
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->latest()
            ->paginate($perPage)
            ->through(function ($transaction) {
                return $transaction->items->map(function ($item) use ($transaction) {
                    return [
                        'test' => $item->test,
                        'purchased_at' => $transaction->created_at,
                        'transaction_number' => $transaction->transaction_number,
                    ];
                });
            })
            ->flatten(1);
    }

    /**
     * Get all purchased test IDs for user.
     */
    public function getPurchasedTestIds(User $user): array
    {
        return PublicTransactionItem::whereHas('transaction', function ($query) use ($user) {
            $query->where('user_id', $user->id)
                ->where('status', 'completed');
        })
            ->pluck('test_id')
            ->unique()
            ->toArray();
    }

    /**
     * Check if user has purchased a test.
     */
    public function hasPurchasedTest(User $user, int $testId): bool
    {
        return PublicTransactionItem::whereHas('transaction', function ($query) use ($user) {
            $query->where('user_id', $user->id)
                ->where('status', 'completed');
        })
            ->where('test_id', $testId)
            ->exists();
    }
}
