<?php

namespace App\Http\Controllers\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\SuccessResource;
use App\Models\Invoice;
use App\Services\Subscription\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $companyAdmin = $user->companyAdmin();

            if (! $companyAdmin) {
                return (new ErrorResource('Company admin not found', 403))->response();
            }

            $company = $companyAdmin->company;
            $status = $request->input('status');

            $invoices = $this->invoiceService->getInvoicesForCompany($company, $status);

            return (new SuccessResource(
                InvoiceResource::collection($invoices)
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    public function show(Invoice $invoice): JsonResponse
    {
        try {
            $user = Auth::user();
            $companyAdmin = $user->companyAdmin();

            if (! $companyAdmin) {
                return (new ErrorResource('Company admin not found', 403))->response();
            }

            $company = $companyAdmin->company;

            // Verify invoice belongs to company
            if (! $this->invoiceService->verifyInvoiceBelongsToCompany($invoice, $company)) {
                return (new ErrorResource('Invoice not found', 404))->response();
            }

            return (new SuccessResource(
                new InvoiceResource($invoice->load(['companySubscription.subscriptionPlan', 'payments']))
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }
}
