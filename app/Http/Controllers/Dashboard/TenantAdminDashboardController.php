<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\TenantAdminDashboardResource;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use App\Services\Dashboard\TenantAdminDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TenantAdminDashboardController extends Controller
{
    public function __construct(
        protected TenantAdminDashboardService $dashboardService
    ) {}

    /**
     * Get TenantAdmin dashboard statistics.
     */
    public function index(): JsonResponse
    {
        try {
            $user = Auth::user();
            $companyAdmin = $user->companyAdmin();

            if (! $companyAdmin) {
                return (new ErrorResource('Company admin not found', 403))->response();
            }

            $company = $companyAdmin->company;
            $statistics = $this->dashboardService->getTenantAdminStatistics($company);

            return (new SuccessResource(
                $statistics,
                'Dashboard statistics retrieved successfully'
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource(
                $e->getMessage(),
                500
            ))->response();
        }
    }
}
