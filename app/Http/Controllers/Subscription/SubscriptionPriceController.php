<?php

namespace App\Http\Controllers\Subscription;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\StoreSubscriptionPriceRequest;
use App\Http\Requests\Subscription\UpdateSubscriptionPriceRequest;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use App\Http\Resources\Subscription\SubscriptionPriceResource;
use App\Services\Subscription\SubscriptionPriceService;
use Illuminate\Http\JsonResponse;

class SubscriptionPriceController extends Controller
{
    public function __construct(
        protected SubscriptionPriceService $subscriptionPriceService
    ) {
    }

    /**
     * Display a listing of subscription prices for a plan.
     */
    public function index(int $planId): JsonResponse
    {
        try {
            $prices = $this->subscriptionPriceService->getByPlan($planId, request()->all());

            return (new SuccessResource(
                SubscriptionPriceResource::collection($prices)->response()->getData(true),
                'Subscription prices retrieved successfully'
            ))->toResponse(request());
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 500))->toResponse(request());
        }
    }

    /**
     * Get all prices for a plan (non-paginated, for dropdowns).
     */
    public function getAllForPlan(int $planId): JsonResponse
    {
        try {
            $prices = $this->subscriptionPriceService->getAllForPlan($planId);

            return (new SuccessResource(
                SubscriptionPriceResource::collection($prices),
                'Subscription prices retrieved successfully'
            ))->toResponse(request());
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 500))->toResponse(request());
        }
    }

    /**
     * Store a newly created subscription price.
     */
    public function store(StoreSubscriptionPriceRequest $request): JsonResponse
    {
        try {
            $price = $this->subscriptionPriceService->create($request->validated());

            return (new SuccessResource(
                new SubscriptionPriceResource($price->load('subscriptionPlan')),
                'Subscription price created successfully',
                201
            ))->toResponse($request);
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 500))->toResponse($request);
        }
    }

    /**
     * Display the specified subscription price.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $price = $this->subscriptionPriceService->getById($id);

            return (new SuccessResource(
                new SubscriptionPriceResource($price),
                'Subscription price retrieved successfully'
            ))->toResponse(request());
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 404))->toResponse(request());
        }
    }

    /**
     * Update the specified subscription price.
     */
    public function update(UpdateSubscriptionPriceRequest $request, int $id): JsonResponse
    {
        try {
            $price = $this->subscriptionPriceService->getById($id);
            $price = $this->subscriptionPriceService->update($price, $request->validated());

            return (new SuccessResource(
                new SubscriptionPriceResource($price),
                'Subscription price updated successfully'
            ))->toResponse($request);
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 500))->toResponse($request);
        }
    }

    /**
     * Remove the specified subscription price.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $price = $this->subscriptionPriceService->getById($id);
            $this->subscriptionPriceService->delete($price);

            return (new SuccessResource(
                null,
                'Subscription price deleted successfully'
            ))->toResponse(request());
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 500))->toResponse(request());
        }
    }
}
