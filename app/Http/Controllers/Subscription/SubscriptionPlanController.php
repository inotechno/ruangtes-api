<?php

namespace App\Http\Controllers\Subscription;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\StoreSubscriptionPlanRequest;
use App\Http\Requests\Subscription\UpdateSubscriptionPlanRequest;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use App\Http\Resources\Subscription\SubscriptionPlanResource;
use App\Services\Subscription\SubscriptionPlanService;
use Illuminate\Http\JsonResponse;

class SubscriptionPlanController extends Controller
{
    public function __construct(
        protected SubscriptionPlanService $subscriptionPlanService
    ) {
    }

    /**
     * Display a listing of subscription plans.
     */
    public function index(): JsonResponse
    {
        try {
            $plans = $this->subscriptionPlanService->getAll(request()->all());

            return (new SuccessResource(
                SubscriptionPlanResource::collection($plans)->response()->getData(true),
                'Subscription plans retrieved successfully'
            ))->toResponse(request());
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 500))->toResponse(request());
        }
    }

    /**
     * Store a newly created subscription plan.
     */
    public function store(StoreSubscriptionPlanRequest $request): JsonResponse
    {
        try {
            $plan = $this->subscriptionPlanService->create($request->validated());

            return (new SuccessResource(
                new SubscriptionPlanResource($plan->load('prices')),
                'Subscription plan created successfully',
                201
            ))->toResponse($request);
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 500))->toResponse($request);
        }
    }

    /**
     * Display the specified subscription plan.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $plan = $this->subscriptionPlanService->getById($id);

            return (new SuccessResource(
                new SubscriptionPlanResource($plan),
                'Subscription plan retrieved successfully'
            ))->toResponse(request());
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 404))->toResponse(request());
        }
    }

    /**
     * Update the specified subscription plan.
     */
    public function update(UpdateSubscriptionPlanRequest $request, int $id): JsonResponse
    {
        try {
            $plan = $this->subscriptionPlanService->getById($id);
            $plan = $this->subscriptionPlanService->update($plan, $request->validated());

            return (new SuccessResource(
                new SubscriptionPlanResource($plan),
                'Subscription plan updated successfully'
            ))->toResponse($request);
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 500))->toResponse($request);
        }
    }

    /**
     * Remove the specified subscription plan.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $plan = $this->subscriptionPlanService->getById($id);
            $this->subscriptionPlanService->delete($plan);

            return (new SuccessResource(
                null,
                'Subscription plan deleted successfully'
            ))->toResponse(request());
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 500))->toResponse(request());
        }
    }
}
