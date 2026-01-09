<?php

namespace App\Http\Controllers\Cart;

use App\Http\Controllers\Controller;
use App\Http\Resources\Cart\CartResource;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use App\Http\Resources\Transaction\TransactionResource;
use App\Services\Cart\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    public function __construct(
        protected CheckoutService $checkoutService
    ) {}

    /**
     * Verify cart before checkout.
     */
    public function verify(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (! $user->isPublicUser()) {
                return (new ErrorResource('Unauthorized', 403))->response();
            }

            $verification = $this->checkoutService->verifyCart($user);

            if (! $verification['valid']) {
                return (new ErrorResource($verification['message'], 400))->response();
            }

            return (new SuccessResource([
                'item_count' => $verification['item_count'],
                'total' => $verification['total'],
                'items' => CartResource::collection($verification['items']),
            ], 'Cart verified successfully'))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Checkout (create transaction from cart).
     */
    public function checkout(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (! $user->isPublicUser()) {
                return (new ErrorResource('Unauthorized', 403))->response();
            }

            $transaction = $this->checkoutService->checkout($user);

            return (new SuccessResource(
                new TransactionResource($transaction),
                'Checkout successful. Transaction created.',
                201
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }
}
