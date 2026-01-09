<?php

namespace App\Http\Controllers\Cart;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\Cart\CartResource;
use App\Http\Resources\SuccessResource;
use App\Services\Cart\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService
    ) {}

    /**
     * Get cart.
     */
    public function show(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (! $user->isPublicUser()) {
                return (new ErrorResource('Unauthorized', 403))->response();
            }

            $cart = $this->cartService->getCart($user);

            if (! $cart) {
                return (new SuccessResource([
                    'cart' => null,
                    'summary' => [
                        'item_count' => 0,
                        'total' => 0,
                    ],
                ], 'Cart is empty'))->response();
            }

            $summary = $this->cartService->getCartSummary($user);

            return (new SuccessResource([
                'cart' => new CartResource($cart),
                'summary' => $summary,
            ], 'Cart retrieved successfully'))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Add test to cart.
     */
    public function add(AddToCartRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (! $user->isPublicUser()) {
                return (new ErrorResource('Unauthorized', 403))->response();
            }

            $cartItem = $this->cartService->addToCart($user, $request->test_id);

            return (new SuccessResource(
                new CartResource($cartItem->cart->load(['items.test'])),
                'Test added to cart successfully'
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Remove test from cart.
     */
    public function remove(int $testId): JsonResponse
    {
        try {
            $user = Auth::user();

            if (! $user->isPublicUser()) {
                return (new ErrorResource('Unauthorized', 403))->response();
            }

            $this->cartService->removeFromCart($user, $testId);

            $cart = $this->cartService->getCart($user);
            $summary = $this->cartService->getCartSummary($user);

            return (new SuccessResource([
                'cart' => $cart ? new CartResource($cart) : null,
                'summary' => $summary,
            ], 'Test removed from cart successfully'))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Clear cart.
     */
    public function clear(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (! $user->isPublicUser()) {
                return (new ErrorResource('Unauthorized', 403))->response();
            }

            $this->cartService->clearCart($user);

            return (new SuccessResource(null, 'Cart cleared successfully'))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }
}
