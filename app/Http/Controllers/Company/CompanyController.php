<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreCompanyRequest;
use App\Http\Requests\Company\UpdateCompanyRequest;
use App\Http\Resources\Company\CompanyResource;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use App\Services\Company\CompanyService;
use Illuminate\Http\JsonResponse;

class CompanyController extends Controller
{
    public function __construct(
        protected CompanyService $companyService
    ) {
    }

    /**
     * Display a listing of companies.
     */
    public function index(): JsonResponse
    {
        try {
            $companies = $this->companyService->getAll(request()->all());

            return (new SuccessResource(
                CompanyResource::collection($companies)->response()->getData(true),
                'Companies retrieved successfully'
            ))->toResponse(request());
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 500))->toResponse(request());
        }
    }

    /**
     * Store a newly created company.
     */
    public function store(StoreCompanyRequest $request): JsonResponse
    {
        try {
            $company = $this->companyService->create($request->validated());

            return (new SuccessResource(
                new CompanyResource($company->load(['admins.user', 'participants', 'subscriptions'])),
                'Company created successfully',
                201
            ))->toResponse($request);
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 500))->toResponse($request);
        }
    }

    /**
     * Display the specified company.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $company = $this->companyService->getById($id);

            return (new SuccessResource(
                new CompanyResource($company),
                'Company retrieved successfully'
            ))->toResponse(request());
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 404))->toResponse(request());
        }
    }

    /**
     * Update the specified company.
     */
    public function update(UpdateCompanyRequest $request, int $id): JsonResponse
    {
        try {
            $company = $this->companyService->getById($id);
            $company = $this->companyService->update($company, $request->validated());

            return (new SuccessResource(
                new CompanyResource($company),
                'Company updated successfully'
            ))->toResponse($request);
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 500))->toResponse($request);
        }
    }

    /**
     * Remove the specified company.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $company = $this->companyService->getById($id);
            $this->companyService->delete($company);

            return (new SuccessResource(
                null,
                'Company deleted successfully'
            ))->toResponse(request());
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 500))->toResponse(request());
        }
    }

    /**
     * Toggle company active status.
     */
    public function toggleActive(int $id): JsonResponse
    {
        try {
            $company = $this->companyService->getById($id);
            $company = $this->companyService->toggleActive($company);

            return (new SuccessResource(
                new CompanyResource($company),
                'Company status updated successfully'
            ))->toResponse(request());
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 500))->toResponse(request());
        }
    }
}
