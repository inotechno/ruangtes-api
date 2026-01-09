<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\DashboardResource;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\JsonResponse;

class SuperAdminDashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {
    }

    /**
     * Get SuperAdmin dashboard statistics.
     */
    public function index(): JsonResponse
    {
        try {
            $statistics = $this->dashboardService->getSuperAdminStatistics();

            return (new SuccessResource(
                $statistics,
                'Dashboard statistics retrieved successfully'
            ))->toResponse(request());
        } catch (\Exception $e) {
            return (new ErrorResource(
                $e->getMessage(),
                500
            ))->toResponse(request());
        }
    }
}
