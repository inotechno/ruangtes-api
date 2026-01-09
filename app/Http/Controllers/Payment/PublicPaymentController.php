<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\UploadPaymentProofRequest;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\Payment\PaymentResource;
use App\Http\Resources\SuccessResource;
use App\Models\Payment;
use App\Models\PublicTransaction;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PublicPaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Upload payment proof for public transaction.
     */
    public function uploadProof(UploadPaymentProofRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (! $user->isPublicUser()) {
                return (new ErrorResource('Unauthorized', 403))->response();
            }

            $transaction = PublicTransaction::where('user_id', $user->id)
                ->where('id', $request->transaction_id)
                ->firstOrFail();

            // Get or create payment
            $payment = $transaction->payment;
            if (! $payment) {
                // Create payment if not exists
                $payment = $this->paymentService->createPaymentForPublicTransaction($transaction);
            }

            // Verify payment belongs to user's transaction
            if ($payment->payable_id !== $transaction->id || $payment->payable_type !== PublicTransaction::class) {
                return (new ErrorResource('Payment not found', 404))->response();
            }

            $file = $request->file('proof_file');
            $payment = $this->paymentService->uploadProofWithNotes(
                $payment,
                $file,
                $request->input('notes')
            );

            return (new SuccessResource(
                new PaymentResource($payment),
                'Payment proof uploaded successfully. Waiting for verification.',
                200
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Get payment for transaction.
     */
    public function show(int $transactionId): JsonResponse
    {
        try {
            $user = Auth::user();

            if (! $user->isPublicUser()) {
                return (new ErrorResource('Unauthorized', 403))->response();
            }

            $transaction = PublicTransaction::where('user_id', $user->id)
                ->where('id', $transactionId)
                ->with('payment')
                ->firstOrFail();

            if (! $transaction->payment) {
                return (new ErrorResource('Payment not found', 404))->response();
            }

            return (new SuccessResource(
                new PaymentResource($transaction->payment),
                'Payment retrieved successfully'
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }
}
