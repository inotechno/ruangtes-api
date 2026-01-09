<?php

namespace App\Services\Dashboard;

use App\Enums\SubscriptionStatus;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Participant;
use App\Models\Test;
use App\Models\TestCategory;
use App\Models\TestSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Get SuperAdmin dashboard statistics.
     */
    public function getSuperAdminStatistics(): array
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfYear = $now->copy()->startOfYear();
        $lastMonth = $now->copy()->subMonth();
        $lastMonthStart = $lastMonth->copy()->startOfMonth();
        $lastMonthEnd = $lastMonth->copy()->endOfMonth();

        return [
            'overview' => $this->getOverviewStats(),
            'companies' => $this->getCompanyStats(),
            'subscriptions' => $this->getSubscriptionStats($startOfMonth, $lastMonthStart, $lastMonthEnd),
            'tests' => $this->getTestStats(),
            'users' => $this->getUserStats(),
            'test_sessions' => $this->getTestSessionStats($startOfMonth, $lastMonthStart, $lastMonthEnd),
            'recent_activities' => $this->getRecentActivities(),
            'growth' => $this->getGrowthStats($startOfMonth, $lastMonthStart, $lastMonthEnd, $startOfYear),
        ];
    }

    /**
     * Get overview statistics.
     */
    protected function getOverviewStats(): array
    {
        return [
            'total_companies' => Company::count(),
            'active_companies' => Company::where('is_active', true)->count(),
            'total_subscriptions' => CompanySubscription::count(),
            'active_subscriptions' => CompanySubscription::where('status', SubscriptionStatus::Active->value)
                ->where('expires_at', '>', now())
                ->count(),
            'total_tests' => Test::count(),
            'active_tests' => Test::where('is_active', true)->count(),
            'total_participants' => Participant::count(),
            'total_public_users' => User::whereHas('roles', fn ($q) => $q->where('name', 'public_user'))->count(),
            'total_test_sessions' => TestSession::count(),
            'completed_test_sessions' => TestSession::where('status', 'completed')->count(),
        ];
    }

    /**
     * Get company statistics.
     */
    protected function getCompanyStats(): array
    {
        return [
            'total' => Company::count(),
            'active' => Company::where('is_active', true)->count(),
            'inactive' => Company::where('is_active', false)->count(),
            'with_subscription' => Company::whereHas('subscriptions', function ($query) {
                $query->where('status', SubscriptionStatus::Active->value)
                    ->where('expires_at', '>', now());
            })->count(),
            'new_this_month' => Company::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];
    }

    /**
     * Get subscription statistics.
     */
    protected function getSubscriptionStats(Carbon $startOfMonth, Carbon $lastMonthStart, Carbon $lastMonthEnd): array
    {
        $activeSubscriptions = CompanySubscription::where('status', SubscriptionStatus::Active->value)
            ->where('expires_at', '>', now())
            ->count();

        $expiringSoon = CompanySubscription::where('status', SubscriptionStatus::Active->value)
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays(30))
            ->count();

        return [
            'total' => CompanySubscription::count(),
            'active' => $activeSubscriptions,
            'expired' => CompanySubscription::where('status', SubscriptionStatus::Expired->value)->count(),
            'cancelled' => CompanySubscription::where('status', SubscriptionStatus::Cancelled->value)->count(),
            'pending' => CompanySubscription::where('status', SubscriptionStatus::Pending->value)->count(),
            'expiring_soon' => $expiringSoon,
            'new_this_month' => CompanySubscription::whereBetween('created_at', [$startOfMonth, now()])->count(),
            'new_last_month' => CompanySubscription::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count(),
        ];
    }

    /**
     * Get test statistics.
     */
    protected function getTestStats(): array
    {
        return [
            'total' => Test::count(),
            'active' => Test::where('is_active', true)->count(),
            'inactive' => Test::where('is_active', false)->count(),
            'by_type' => [
                'public' => Test::where('type', 'public')->count(),
                'company' => Test::where('type', 'company')->count(),
                'all' => Test::where('type', 'all')->count(),
            ],
            'total_categories' => TestCategory::count(),
            'active_categories' => TestCategory::where('is_active', true)->count(),
        ];
    }

    /**
     * Get user statistics.
     */
    protected function getUserStats(): array
    {
        return [
            'total_users' => User::count(),
            'super_admins' => User::whereHas('roles', fn ($q) => $q->where('name', 'super_admin'))->count(),
            'tenant_admins' => User::whereHas('roles', fn ($q) => $q->where('name', 'tenant_admin'))->count(),
            'public_users' => User::whereHas('roles', fn ($q) => $q->where('name', 'public_user'))->count(),
            'verified_users' => User::whereNotNull('email_verified_at')->count(),
            'unverified_users' => User::whereNull('email_verified_at')->count(),
        ];
    }

    /**
     * Get test session statistics.
     */
    protected function getTestSessionStats(Carbon $startOfMonth, Carbon $lastMonthStart, Carbon $lastMonthEnd): array
    {
        return [
            'total' => TestSession::count(),
            'completed' => TestSession::where('status', 'completed')->count(),
            'in_progress' => TestSession::where('status', 'in_progress')->count(),
            'abandoned' => TestSession::where('status', 'abandoned')->count(),
            'banned' => TestSession::where('status', 'banned')->count(),
            'this_month' => TestSession::whereBetween('created_at', [$startOfMonth, now()])->count(),
            'last_month' => TestSession::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count(),
        ];
    }

    /**
     * Get recent activities.
     */
    protected function getRecentActivities(): array
    {
        $activities = [];

        // Recent companies
        $recentCompanies = Company::latest()->limit(5)->get();
        foreach ($recentCompanies as $company) {
            $activities[] = [
                'type' => 'company_created',
                'title' => "Company '{$company->name}' created",
                'description' => "New company registered",
                'created_at' => $company->created_at,
            ];
        }

        // Recent subscriptions
        $recentSubscriptions = CompanySubscription::with('company')->latest()->limit(5)->get();
        foreach ($recentSubscriptions as $subscription) {
            $activities[] = [
                'type' => 'subscription_created',
                'title' => "Subscription created for '{$subscription->company->name}'",
                'description' => "New subscription with status: {$subscription->status}",
                'created_at' => $subscription->created_at,
            ];
        }

        // Sort by created_at desc and limit to 10
        usort($activities, fn ($a, $b) => $b['created_at'] <=> $a['created_at']);

        return array_slice($activities, 0, 10);
    }

    /**
     * Get growth statistics.
     */
    protected function getGrowthStats(Carbon $startOfMonth, Carbon $lastMonthStart, Carbon $lastMonthEnd, Carbon $startOfYear): array
    {
        $companiesThisMonth = Company::whereBetween('created_at', [$startOfMonth, now()])->count();
        $companiesLastMonth = Company::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count();
        $companiesThisYear = Company::whereBetween('created_at', [$startOfYear, now()])->count();

        $subscriptionsThisMonth = CompanySubscription::whereBetween('created_at', [$startOfMonth, now()])->count();
        $subscriptionsLastMonth = CompanySubscription::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count();

        $testSessionsThisMonth = TestSession::whereBetween('created_at', [$startOfMonth, now()])->count();
        $testSessionsLastMonth = TestSession::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count();

        return [
            'companies' => [
                'this_month' => $companiesThisMonth,
                'last_month' => $companiesLastMonth,
                'this_year' => $companiesThisYear,
                'growth_percentage' => $companiesLastMonth > 0
                    ? round((($companiesThisMonth - $companiesLastMonth) / $companiesLastMonth) * 100, 2)
                    : ($companiesThisMonth > 0 ? 100 : 0),
            ],
            'subscriptions' => [
                'this_month' => $subscriptionsThisMonth,
                'last_month' => $subscriptionsLastMonth,
                'growth_percentage' => $subscriptionsLastMonth > 0
                    ? round((($subscriptionsThisMonth - $subscriptionsLastMonth) / $subscriptionsLastMonth) * 100, 2)
                    : ($subscriptionsThisMonth > 0 ? 100 : 0),
            ],
            'test_sessions' => [
                'this_month' => $testSessionsThisMonth,
                'last_month' => $testSessionsLastMonth,
                'growth_percentage' => $testSessionsLastMonth > 0
                    ? round((($testSessionsThisMonth - $testSessionsLastMonth) / $testSessionsLastMonth) * 100, 2)
                    : ($testSessionsThisMonth > 0 ? 100 : 0),
            ],
        ];
    }
}
