<?php

namespace App\Http\Controllers\Test;

use App\Http\Controllers\Controller;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\Test\TestCatalogResource;
use App\Http\Resources\SuccessResource;
use App\Services\Test\TestCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TestCatalogController extends Controller
{
    public function __construct(
        protected TestCatalogService $catalogService
    ) {}

    /**
     * Browse available tests.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $tests = $this->catalogService->getAvailableTests(
                $request->integer('category_id'),
                $request->input('search'),
                $request->input('per_page', 15)
            );

            return (new SuccessResource(
                TestCatalogResource::collection($tests)
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Get test details.
     */
    public function show(int $testId): JsonResponse
    {
        try {
            $test = $this->catalogService->getTestDetails($testId);

            if (! $test) {
                return (new ErrorResource('Test not found or not available', 404))->response();
            }

            // Check if user has purchased this test
            $isPurchased = false;
            if (Auth::check() && Auth::user()->isPublicUser()) {
                $transactionService = app(\App\Services\Transaction\TransactionService::class);
                $isPurchased = $transactionService->hasPurchasedTest(Auth::user(), $testId);
            }

            return (new SuccessResource([
                'test' => new TestCatalogResource($test),
                'is_purchased' => $isPurchased,
            ], 'Test details retrieved successfully'))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Get test categories.
     */
    public function categories(): JsonResponse
    {
        try {
            $categories = $this->catalogService->getCategories();

            return (new SuccessResource($categories, 'Categories retrieved successfully'))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }
}
