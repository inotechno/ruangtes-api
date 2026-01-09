<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use App\Http\Resources\Test\TestCatalogResource;
use App\Http\Resources\Transaction\TransactionResource;
use App\Services\Transaction\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    /**
     * Get transaction history.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (! $user->isPublicUser()) {
                return (new ErrorResource('Unauthorized', 403))->response();
            }

            $transactions = $this->transactionService->getTransactions(
                $user,
                $request->input('status'),
                $request->input('per_page', 15)
            );

            return (new SuccessResource(
                TransactionResource::collection($transactions)
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Get transaction details.
     */
    public function show(int $transactionId): JsonResponse
    {
        try {
            $user = Auth::user();

            if (! $user->isPublicUser()) {
                return (new ErrorResource('Unauthorized', 403))->response();
            }

            $transaction = $this->transactionService->getTransaction($user, $transactionId);

            if (! $transaction) {
                return (new ErrorResource('Transaction not found', 404))->response();
            }

            return (new SuccessResource(
                new TransactionResource($transaction),
                'Transaction details retrieved successfully'
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Get purchased tests.
     */
    public function purchasedTests(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (! $user->isPublicUser()) {
                return (new ErrorResource('Unauthorized', 403))->response();
            }

            $purchasedTests = $this->transactionService->getPurchasedTests(
                $user,
                $request->input('per_page', 15)
            );

            // Transform the data
            $tests = $purchasedTests->getCollection()->map(function ($item) {
                return [
                    'test' => new TestCatalogResource($item['test']),
                    'purchased_at' => $item['purchased_at']->toIso8601String(),
                    'transaction_number' => $item['transaction_number'],
                ];
            });

            return (new SuccessResource([
                'data' => $tests,
                'pagination' => [
                    'current_page' => $purchasedTests->currentPage(),
                    'last_page' => $purchasedTests->lastPage(),
                    'per_page' => $purchasedTests->perPage(),
                    'total' => $purchasedTests->total(),
                ],
            ], 'Purchased tests retrieved successfully'))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }
}
