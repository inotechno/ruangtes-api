<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Test;
use App\Models\User;
use App\Services\Test\TestCatalogService;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function __construct(
        protected TestCatalogService $catalogService
    ) {}

    /**
     * Get or create cart for user.
     */
    public function getOrCreateCart(User $user): Cart
    {
        return Cart::firstOrCreate(
            ['user_id' => $user->id],
            ['user_id' => $user->id]
        );
    }

    /**
     * Get cart with items.
     */
    public function getCart(User $user): ?Cart
    {
        return Cart::with(['items.test.category'])
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * Add test to cart.
     */
    public function addToCart(User $user, int $testId): CartItem
    {
        $test = Test::findOrFail($testId);

        // Verify test is available for public users
        if (! $this->catalogService->isTestAvailable($test)) {
            throw new \Exception('Test is not available for purchase.');
        }

        $cart = $this->getOrCreateCart($user);

        // Check if test already in cart
        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('test_id', $testId)
            ->first();

        if ($cartItem) {
            throw new \Exception('Test already in cart.');
        }

        // Add to cart
        return CartItem::create([
            'cart_id' => $cart->id,
            'test_id' => $testId,
        ]);
    }

    /**
     * Remove test from cart.
     */
    public function removeFromCart(User $user, int $testId): bool
    {
        $cart = $this->getCart($user);

        if (! $cart) {
            throw new \Exception('Cart not found.');
        }

        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('test_id', $testId)
            ->first();

        if (! $cartItem) {
            throw new \Exception('Item not found in cart.');
        }

        return $cartItem->delete();
    }

    /**
     * Clear cart.
     */
    public function clearCart(User $user): bool
    {
        $cart = $this->getCart($user);

        if (! $cart) {
            return false;
        }

        return $cart->items()->delete();
    }

    /**
     * Get cart summary (total, item count).
     */
    public function getCartSummary(User $user): array
    {
        $cart = $this->getCart($user);

        if (! $cart) {
            return [
                'item_count' => 0,
                'total' => 0,
                'items' => [],
            ];
        }

        $cart->load(['items.test']);

        return [
            'item_count' => $cart->item_count,
            'total' => $cart->total,
            'items' => $cart->items,
        ];
    }
}
