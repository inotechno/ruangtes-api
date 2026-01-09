<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\PublicTransaction;
use App\Models\PublicTransactionItem;
use App\Models\User;
use App\Services\Payment\PaymentService;
use App\Services\Test\TestCatalogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutService
{
    public function __construct(
        protected CartService $cartService,
        protected TestCatalogService $catalogService,
        protected PaymentService $paymentService
    ) {}

    /**
     * Create transaction from cart (checkout).
     */
    public function checkout(User $user): PublicTransaction
    {
        $cart = $this->cartService->getCart($user);

        if (! $cart || $cart->items->isEmpty()) {
            throw new \Exception('Cart is empty.');
        }

        return DB::transaction(function () use ($user, $cart) {
            // Generate transaction number
            $transactionNumber = 'TRX-'.strtoupper(Str::random(10));

            // Calculate total
            $total = $cart->items->sum(function ($item) {
                return $item->test->price;
            });

            // Create transaction
            $transaction = PublicTransaction::create([
                'user_id' => $user->id,
                'transaction_number' => $transactionNumber,
                'total_amount' => $total,
                'status' => 'pending',
            ]);

            // Create transaction items
            foreach ($cart->items as $cartItem) {
                PublicTransactionItem::create([
                    'public_transaction_id' => $transaction->id,
                    'test_id' => $cartItem->test_id,
                    'price' => $cartItem->test->price,
                ]);
            }

            // Auto-create payment record
            $this->paymentService->createPaymentForPublicTransaction($transaction);

            // Clear cart
            $this->cartService->clearCart($user);

            return $transaction->load(['items.test', 'payment']);
        });
    }

    /**
     * Verify cart before checkout.
     */
    public function verifyCart(User $user): array
    {
        $cart = $this->cartService->getCart($user);

        if (! $cart || $cart->items->isEmpty()) {
            return [
                'valid' => false,
                'message' => 'Cart is empty.',
            ];
        }

        $cart->load(['items.test']);

        // Check if all tests are still available
        $unavailableTests = [];

        foreach ($cart->items as $item) {
            if (! $this->catalogService->isTestAvailable($item->test)) {
                $unavailableTests[] = $item->test->name;
            }
        }

        if (! empty($unavailableTests)) {
            return [
                'valid' => false,
                'message' => 'Some tests are no longer available: '.implode(', ', $unavailableTests),
                'unavailable_tests' => $unavailableTests,
            ];
        }

        $total = $cart->items->sum(function ($item) {
            return $item->test->price;
        });

        return [
            'valid' => true,
            'item_count' => $cart->items->count(),
            'total' => $total,
            'items' => $cart->items,
        ];
    }
}
