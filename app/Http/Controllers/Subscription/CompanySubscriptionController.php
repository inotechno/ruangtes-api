<?php

namespace App\Http\Controllers\Subscription;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\ExtendSubscriptionRequest;
use App\Http\Requests\Subscription\PurchaseQuotaRequest;
use App\Http\Requests\Subscription\PurchaseSubscriptionRequest;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use App\Http\Resources\SubscriptionResource;
use App\Http\Resources\SubscriptionTransactionResource;
use App\Http\Resources\SubscriptionPlanResource;
use App\Models\CompanySubscription;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPrice;
use App\Services\Payment\PaymentService;
use App\Services\Subscription\InvoiceService;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanySubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected PaymentService $paymentService,
        protected InvoiceService $invoiceService
    ) {}

    public function availablePlans(Request $request): JsonResponse
    {
        try {
            $plans = $this->subscriptionService->getAvailablePlans();

            return (new SuccessResource(
                SubscriptionPlanResource::collection($plans)
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    public function purchase(PurchaseSubscriptionRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $companyAdmin = $user->companyAdmin();

            if (! $companyAdmin) {
                return (new ErrorResource('Company admin not found', 403))->response();
            }

            $company = $companyAdmin->company;
            $plan = SubscriptionPlan::findOrFail($request->subscription_plan_id);
            $price = SubscriptionPrice::findOrFail($request->subscription_price_id);
            $billingType = $request->billing_type;
            $additionalUsers = $request->input('additional_users', 0);

            if ($billingType === 'pre_paid') {
                $subscription = $this->subscriptionService->purchasePrePaid(
                    $company,
                    $plan,
                    $price,
                    $additionalUsers
                );

                // Create payment record
                $transaction = $subscription->transactions()->latest()->first();
                $payment = $this->paymentService->createPaymentForTransaction($transaction);

                return (new SuccessResource(
                    new SubscriptionResource($subscription->load(['subscriptionPlan', 'subscriptionPrice', 'transactions.payment'])),
                    'Subscription purchased successfully. Please upload payment proof.',
                    201
                ))->response();
            } else {
                // Post-paid
                $subscription = $this->subscriptionService->purchasePostPaid(
                    $company,
                    $plan,
                    $price,
                    $additionalUsers
                );

                return (new SuccessResource(
                    new SubscriptionResource($subscription->load(['subscriptionPlan', 'subscriptionPrice'])),
                    'Subscription activated successfully. Invoice will be generated at the end of period.',
                    201
                ))->response();
            }
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    public function active(): JsonResponse
    {
        try {
            $user = Auth::user();
            $companyAdmin = $user->companyAdmin();

            if (! $companyAdmin) {
                return (new ErrorResource('Company admin not found', 403))->response();
            }

            $company = $companyAdmin->company;
            $subscription = $this->subscriptionService->getActiveSubscriptionForCompany($company);

            if (! $subscription) {
                return (new ErrorResource('No active subscription found', 404))->response();
            }

            return (new SuccessResource(
                new SubscriptionResource($subscription->load(['subscriptionPlan', 'subscriptionPrice', 'transactions.payment', 'invoices.payments']))
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    public function history(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $companyAdmin = $user->companyAdmin();

            if (! $companyAdmin) {
                return (new ErrorResource('Company admin not found', 403))->response();
            }

            $company = $companyAdmin->company;
            $subscriptions = $this->subscriptionService->getSubscriptionHistoryForCompany(
                $company,
                $request->input('per_page', 15)
            );

            return (new SuccessResource(
                SubscriptionResource::collection($subscriptions)
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    public function purchaseQuota(PurchaseQuotaRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $companyAdmin = $user->companyAdmin();

            if (! $companyAdmin) {
                return (new ErrorResource('Company admin not found', 403))->response();
            }

            $company = $companyAdmin->company;
            $subscription = $this->subscriptionService->getActiveSubscriptionForCompany($company);

            if (! $subscription) {
                return (new ErrorResource('No active subscription found', 404))->response();
            }

            $transaction = $this->subscriptionService->purchaseUserQuota(
                $subscription,
                $request->additional_users
            );

            // Create payment record
            $payment = $this->paymentService->createPaymentForTransaction($transaction);

            return (new SuccessResource(
                new SubscriptionTransactionResource($transaction->load('payment')),
                'Quota purchase successful. Please upload payment proof.',
                201
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    public function extend(ExtendSubscriptionRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $companyAdmin = $user->companyAdmin();

            if (! $companyAdmin) {
                return (new ErrorResource('Company admin not found', 403))->response();
            }

            $company = $companyAdmin->company;
            $subscription = $this->subscriptionService->getActiveSubscriptionForCompany($company);

            if (! $subscription) {
                return (new ErrorResource('No active subscription found', 404))->response();
            }

            $subscription = $this->subscriptionService->extendSubscription(
                $subscription,
                $request->months
            );

            // Generate invoice for extension
            $invoice = $this->invoiceService->generateInvoiceForExtension(
                $subscription,
                $request->months
            );

            // Create payment record
            $payment = $this->paymentService->createPaymentForInvoice($invoice);

            return (new SuccessResource(
                new SubscriptionResource($subscription->load(['subscriptionPlan', 'subscriptionPrice'])),
                'Subscription extended successfully. Please pay the invoice.',
                200
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    public function cancel(CompanySubscription $subscription): JsonResponse
    {
        try {
            $user = Auth::user();
            $companyAdmin = $user->companyAdmin();

            if (! $companyAdmin) {
                return (new ErrorResource('Company admin not found', 403))->response();
            }

            $company = $companyAdmin->company;

            // Verify subscription belongs to company
            if (! $this->subscriptionService->verifySubscriptionBelongsToCompany($subscription, $company)) {
                return (new ErrorResource('Subscription not found', 404))->response();
            }

            $subscription = $this->subscriptionService->cancelSubscription($subscription);

            return (new SuccessResource(
                new SubscriptionResource($subscription),
                'Subscription cancelled successfully.',
                200
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }
}
