<?php

namespace App\Http\Controllers\Test;

use App\Http\Controllers\Controller;
use App\Http\Requests\Test\StoreTestCategoryRequest;
use App\Http\Requests\Test\UpdateTestCategoryRequest;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use App\Http\Resources\Test\TestCategoryResource;
use App\Services\Test\TestCategoryService;
use Illuminate\Http\JsonResponse;

class TestCategoryController extends Controller
{
    public function __construct(
        protected TestCategoryService $testCategoryService
    ) {
    }

    /**
     * Display a listing of test categories.
     */
    public function index(): JsonResponse
    {
        try {
            $categories = $this->testCategoryService->getAll(request()->all());

            return (new SuccessResource(
                TestCategoryResource::collection($categories)->response()->getData(true),
                'Test categories retrieved successfully'
            ))->toResponse(request());
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 500))->toResponse(request());
        }
    }

    /**
     * Store a newly created test category.
     */
    public function store(StoreTestCategoryRequest $request): JsonResponse
    {
        try {
            $category = $this->testCategoryService->create($request->validated());

            return (new SuccessResource(
                new TestCategoryResource($category),
                'Test category created successfully',
                201
            ))->toResponse($request);
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 500))->toResponse($request);
        }
    }

    /**
     * Display the specified test category.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $category = $this->testCategoryService->getById($id);

            return (new SuccessResource(
                new TestCategoryResource($category),
                'Test category retrieved successfully'
            ))->toResponse(request());
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 404))->toResponse(request());
        }
    }

    /**
     * Update the specified test category.
     */
    public function update(UpdateTestCategoryRequest $request, int $id): JsonResponse
    {
        try {
            $category = $this->testCategoryService->getById($id);
            $category = $this->testCategoryService->update($category, $request->validated());

            return (new SuccessResource(
                new TestCategoryResource($category),
                'Test category updated successfully'
            ))->toResponse($request);
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 500))->toResponse($request);
        }
    }

    /**
     * Remove the specified test category.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $category = $this->testCategoryService->getById($id);
            $this->testCategoryService->delete($category);

            return (new SuccessResource(
                null,
                'Test category deleted successfully'
            ))->toResponse(request());
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 500))->toResponse(request());
        }
    }
}
