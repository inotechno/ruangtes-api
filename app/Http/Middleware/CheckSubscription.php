<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        // SuperAdmin doesn't need subscription
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // PublicUser doesn't need subscription (they buy tests individually)
        if ($user->isPublicUser()) {
            return $next($request);
        }

        // TenantAdmin needs active subscription
        if ($user->isTenantAdmin()) {
            $companyAdmin = $user->companyAdmin();
            if (! $companyAdmin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company admin not found',
                ], 403);
            }

            $subscription = $companyAdmin->company->activeSubscription();

            if (! $subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active subscription found. Please subscribe to continue.',
                ], 403);
            }
        }

        return $next($request);
    }
}
