<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\UploadPaymentProofRequest;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\SuccessResource;
use App\Models\Payment;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyPaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    public function uploadProof(UploadPaymentProofRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $companyAdmin = $user->companyAdmin();

            if (! $companyAdmin) {
                return (new ErrorResource('Company admin not found', 403))->response();
            }

            $payment = Payment::findOrFail($request->payment_id);
            $company = $companyAdmin->company;

            if (! $this->paymentService->verifyPaymentBelongsToCompany($payment, $company)) {
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

    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $companyAdmin = $user->companyAdmin();

            if (! $companyAdmin) {
                return (new ErrorResource('Company admin not found', 403))->response();
            }

            $company = $companyAdmin->company;
            $payments = $this->paymentService->getPaymentsForCompany($company);

            return (new SuccessResource(
                PaymentResource::collection($payments)
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    public function show(Payment $payment): JsonResponse
    {
        try {
            $user = Auth::user();
            $companyAdmin = $user->companyAdmin();

            if (! $companyAdmin) {
                return (new ErrorResource('Company admin not found', 403))->response();
            }

            $company = $companyAdmin->company;

            if (! $this->paymentService->verifyPaymentBelongsToCompany($payment, $company)) {
                return (new ErrorResource('Payment not found', 404))->response();
            }

            return (new SuccessResource(
                new PaymentResource($payment)
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }
}
