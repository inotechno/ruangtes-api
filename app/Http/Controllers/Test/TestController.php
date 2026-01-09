<?php

namespace App\Http\Controllers\Test;

use App\Http\Controllers\Controller;
use App\Http\Requests\Test\StoreTestRequest;
use App\Http\Requests\Test\UpdateTestRequest;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use App\Http\Resources\Test\TestResource;
use App\Services\Test\TestService;
use Illuminate\Http\JsonResponse;

class TestController extends Controller
{
    public function __construct(
        protected TestService $testService
    ) {
    }

    /**
     * Display a listing of tests.
     */
    public function index(): JsonResponse
    {
        try {
            $tests = $this->testService->getAll(request()->all());

            return (new SuccessResource(
                TestResource::collection($tests)->response()->getData(true),
                'Tests retrieved successfully'
            ))->toResponse(request());
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 500))->toResponse(request());
        }
    }

    /**
     * Store a newly created test.
     */
    public function store(StoreTestRequest $request): JsonResponse
    {
        try {
            $test = $this->testService->create($request->validated());

            return (new SuccessResource(
                new TestResource($test->load('category')),
                'Test created successfully',
                201
            ))->toResponse($request);
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 500))->toResponse($request);
        }
    }

    /**
     * Display the specified test.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $test = $this->testService->getById($id);

            return (new SuccessResource(
                new TestResource($test),
                'Test retrieved successfully'
            ))->toResponse(request());
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 404))->toResponse(request());
        }
    }

    /**
     * Update the specified test.
     */
    public function update(UpdateTestRequest $request, int $id): JsonResponse
    {
        try {
            $test = $this->testService->getById($id);
            $test = $this->testService->update($test, $request->validated());

            return (new SuccessResource(
                new TestResource($test),
                'Test updated successfully'
            ))->toResponse($request);
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 500))->toResponse($request);
        }
    }

    /**
     * Remove the specified test.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $test = $this->testService->getById($id);
            $this->testService->delete($test);

            return (new SuccessResource(
                null,
                'Test deleted successfully'
            ))->toResponse(request());
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 500))->toResponse(request());
        }
    }
}
