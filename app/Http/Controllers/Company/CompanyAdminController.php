<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreCompanyAdminRequest;
use App\Http\Resources\Company\CompanyAdminResource;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use App\Services\Company\CompanyAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;

class CompanyAdminController extends Controller
{
    public function __construct(
        protected CompanyAdminService $companyAdminService
    ) {
    }

    /**
     * Display a listing of admins for a company.
     */
    public function index(int $companyId): JsonResponse
    {
        try {
            $admins = $this->companyAdminService->getByCompany($companyId);

            return (new SuccessResource(
                CompanyAdminResource::collection($admins),
                'Company admins retrieved successfully'
            ))->toResponse(request());
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 500))->toResponse(request());
        }
    }

    /**
     * Display the specified admin.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $admin = $this->companyAdminService->getById($id);

            return (new SuccessResource(
                new CompanyAdminResource($admin),
                'Company admin retrieved successfully'
            ))->toResponse(request());
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 404))->toResponse(request());
        }
    }

    /**
     * Store a newly created admin for a company.
     */
    public function store(StoreCompanyAdminRequest $request, int $companyId): JsonResponse
    {
        try {
            $admin = $this->companyAdminService->create($companyId, $request->validated());

            return (new SuccessResource(
                new CompanyAdminResource($admin),
                'Company admin created successfully',
                201
            ))->toResponse($request);
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 500))->toResponse($request);
        }
    }

    /**
     * Update the specified admin.
     */
    public function update(HttpRequest $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => ['sometimes', 'required', 'string', 'max:255'],
                'email' => ['sometimes', 'required', 'email', 'max:255', 'unique:users,email,'.$id.',id'],
                'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
                'is_primary' => ['boolean'],
            ]);

            $admin = $this->companyAdminService->getById($id);
            $admin = $this->companyAdminService->update($admin, $validated);

            return (new SuccessResource(
                new CompanyAdminResource($admin),
                'Company admin updated successfully'
            ))->toResponse($request);
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 500))->toResponse($request);
        }
    }

    /**
     * Remove the specified admin.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $admin = $this->companyAdminService->getById($id);
            $this->companyAdminService->delete($admin);

            return (new SuccessResource(
                null,
                'Company admin deleted successfully'
            ))->toResponse(request());
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 500))->toResponse(request());
        }
    }

    /**
     * Set admin as primary.
     */
    public function setPrimary(int $id): JsonResponse
    {
        try {
            $admin = $this->companyAdminService->getById($id);
            $admin = $this->companyAdminService->setPrimary($admin);

            return (new SuccessResource(
                new CompanyAdminResource($admin),
                'Primary admin set successfully'
            ))->toResponse(request());
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 500))->toResponse(request());
        }
    }
}
