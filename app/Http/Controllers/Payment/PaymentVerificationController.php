<?php

namespace App\Http\Controllers\Payment;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\VerifyPaymentRequest;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\Payment\PaymentResource;
use App\Http\Resources\SuccessResource;
use App\Models\Payment;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;

class PaymentVerificationController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Verify a payment (approve or reject).
     */
    public function verify(VerifyPaymentRequest $request): JsonResponse
    {
        try {
            $payment = Payment::findOrFail($request->payment_id);

            if ($payment->status->value !== PaymentStatus::Pending) {
                return (new ErrorResource(
                    'Payment is not pending. Current status: '.$payment->status->value,
                    400
                ))->response();
            }

            $payment = $this->paymentService->verifyPayment(
                $payment,
                $request->approved,
                $request->notes
            );

            return (new SuccessResource(
                new PaymentResource($payment),
                $request->approved
                    ? 'Payment verified and approved successfully.'
                    : 'Payment verification rejected.'
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Get all payments with optional filters.
     */
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        try {
            $payments = $this->paymentService->getAllPayments(
                $request->input('status'),
                $request->input('method'),
                $request->input('per_page', 15)
            );

            return (new SuccessResource(
                PaymentResource::collection($payments)
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Get pending payments for verification.
     */
    public function pending(): JsonResponse
    {
        try {
            $payments = Payment::where('status', PaymentStatus::Pending)
                ->with(['payable'])
                ->latest()
                ->paginate(15);

            return (new SuccessResource(
                PaymentResource::collection($payments)
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Get payment detail for verification.
     */
    public function show(Payment $payment): JsonResponse
    {
        try {
            return (new SuccessResource(
                new PaymentResource($payment->load('payable'))
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }
}
