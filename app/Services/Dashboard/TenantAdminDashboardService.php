<?php

namespace App\Services\Dashboard;

use App\Enums\SubscriptionStatus;
use App\Models\Company;
use App\Models\Participant;
use App\Models\TestAssignment;
use App\Models\TestSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TenantAdminDashboardService
{
    /**
     * Get TenantAdmin dashboard statistics for their company.
     */
    public function getTenantAdminStatistics(Company $company): array
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfYear = $now->copy()->startOfYear();
        $lastMonth = $now->copy()->subMonth();
        $lastMonthStart = $lastMonth->copy()->startOfMonth();
        $lastMonthEnd = $lastMonth->copy()->endOfMonth();

        return [
            'company' => $this->getCompanyInfo($company),
            'subscription' => $this->getSubscriptionInfo($company),
            'overview' => $this->getOverviewStats($company),
            'participants' => $this->getParticipantStats($company),
            'test_assignments' => $this->getTestAssignmentStats($company, $startOfMonth, $lastMonthStart, $lastMonthEnd),
            'test_sessions' => $this->getTestSessionStats($company, $startOfMonth, $lastMonthStart, $lastMonthEnd),
            'recent_activities' => $this->getRecentActivities($company),
            'growth' => $this->getGrowthStats($company, $startOfMonth, $lastMonthStart, $lastMonthEnd, $startOfYear),
        ];
    }

    /**
     * Get company information.
     */
    protected function getCompanyInfo(Company $company): array
    {
        return [
            'id' => $company->id,
            'name' => $company->name,
            'email' => $company->email,
            'phone' => $company->phone,
            'is_active' => $company->is_active,
        ];
    }

    /**
     * Get subscription information.
     */
    protected function getSubscriptionInfo(Company $company): ?array
    {
        $subscription = $company->activeSubscription();

        if (! $subscription) {
            return null;
        }

        $daysUntilExpiry = now()->diffInDays($subscription->expires_at, false);

        return [
            'id' => $subscription->id,
            'status' => $subscription->status->value,
            'billing_type' => $subscription->billing_type,
            'plan_name' => $subscription->subscriptionPlan->name ?? null,
            'duration_months' => $subscription->subscriptionPlan->duration_months ?? null,
            'user_quota' => $subscription->user_quota,
            'used_quota' => $subscription->used_quota,
            'remaining_quota' => $subscription->getRemainingQuota(),
            'quota_usage_percentage' => $subscription->user_quota > 0
                ? round(($subscription->used_quota / $subscription->user_quota) * 100, 2)
                : 0,
            'started_at' => $subscription->started_at?->toIso8601String(),
            'expires_at' => $subscription->expires_at?->toIso8601String(),
            'days_until_expiry' => $daysUntilExpiry,
            'is_expiring_soon' => $daysUntilExpiry <= 30 && $daysUntilExpiry > 0,
        ];
    }

    /**
     * Get overview statistics.
     */
    protected function getOverviewStats(Company $company): array
    {
        return [
            'total_participants' => Participant::where('company_id', $company->id)->count(),
            'active_participants' => Participant::where('company_id', $company->id)
                ->whereNull('banned_at')
                ->count(),
            'banned_participants' => Participant::where('company_id', $company->id)
                ->whereNotNull('banned_at')
                ->count(),
            'total_test_assignments' => TestAssignment::whereHas('participant', function ($query) use ($company) {
                $query->where('company_id', $company->id);
            })->count(),
            'completed_assignments' => TestAssignment::whereHas('participant', function ($query) use ($company) {
                $query->where('company_id', $company->id);
            })->where('is_completed', true)->count(),
            'pending_assignments' => TestAssignment::whereHas('participant', function ($query) use ($company) {
                $query->where('company_id', $company->id);
            })->where('is_completed', false)->count(),
            'total_test_sessions' => TestSession::whereHasMorph('testable', [Participant::class], function ($query) use ($company) {
                $query->where('company_id', $company->id);
            })->count(),
            'completed_sessions' => TestSession::whereHasMorph('testable', [Participant::class], function ($query) use ($company) {
                $query->where('company_id', $company->id);
            })->where('status', 'completed')->count(),
        ];
    }

    /**
     * Get participant statistics.
     */
    protected function getParticipantStats(Company $company): array
    {
        $total = Participant::where('company_id', $company->id)->count();
        $active = Participant::where('company_id', $company->id)
            ->whereNull('banned_at')
            ->count();
        $banned = Participant::where('company_id', $company->id)
            ->whereNotNull('banned_at')
            ->count();

        $newThisMonth = Participant::where('company_id', $company->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return [
            'total' => $total,
            'active' => $active,
            'banned' => $banned,
            'new_this_month' => $newThisMonth,
        ];
    }

    /**
     * Get test assignment statistics.
     */
    protected function getTestAssignmentStats(Company $company, Carbon $startOfMonth, Carbon $lastMonthStart, Carbon $lastMonthEnd): array
    {
        $query = TestAssignment::whereHas('participant', function ($q) use ($company) {
            $q->where('company_id', $company->id);
        });

        $total = $query->count();
        $completed = (clone $query)->where('is_completed', true)->count();
        $pending = (clone $query)->where('is_completed', false)->count();

        // Active assignments (not expired, not completed)
        $active = (clone $query)
            ->where('is_completed', false)
            ->where('end_date', '>=', now())
            ->where('start_date', '<=', now())
            ->count();

        // Expired assignments
        $expired = (clone $query)
            ->where('is_completed', false)
            ->where('end_date', '<', now())
            ->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'pending' => $pending,
            'active' => $active,
            'expired' => $expired,
            'this_month' => (clone $query)->whereBetween('created_at', [$startOfMonth, now()])->count(),
            'last_month' => (clone $query)->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count(),
        ];
    }

    /**
     * Get test session statistics.
     */
    protected function getTestSessionStats(Company $company, Carbon $startOfMonth, Carbon $lastMonthStart, Carbon $lastMonthEnd): array
    {
        $query = TestSession::whereHasMorph('testable', [Participant::class], function ($q) use ($company) {
            $q->where('company_id', $company->id);
        });

        return [
            'total' => $query->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'in_progress' => (clone $query)->where('status', 'in_progress')->count(),
            'abandoned' => (clone $query)->where('status', 'abandoned')->count(),
            'banned' => (clone $query)->where('status', 'banned')->count(),
            'this_month' => (clone $query)->whereBetween('created_at', [$startOfMonth, now()])->count(),
            'last_month' => (clone $query)->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count(),
        ];
    }

    /**
     * Get recent activities.
     */
    protected function getRecentActivities(Company $company): array
    {
        $activities = [];

        // Recent participants
        $recentParticipants = Participant::where('company_id', $company->id)
            ->latest()
            ->limit(5)
            ->get();
        foreach ($recentParticipants as $participant) {
            $activities[] = [
                'type' => 'participant_created',
                'title' => "Participant '{$participant->name}' added",
                'description' => $participant->email,
                'created_at' => $participant->created_at,
            ];
        }

        // Recent test assignments
        $recentAssignments = TestAssignment::whereHas('participant', function ($q) use ($company) {
            $q->where('company_id', $company->id);
        })
            ->with(['participant', 'test'])
            ->latest()
            ->limit(5)
            ->get();
        foreach ($recentAssignments as $assignment) {
            $activities[] = [
                'type' => 'test_assigned',
                'title' => "Test '{$assignment->test->name}' assigned to '{$assignment->participant->name}'",
                'description' => "Due: {$assignment->end_date->format('Y-m-d')}",
                'created_at' => $assignment->created_at,
            ];
        }

        // Recent completed sessions
        $recentSessions = TestSession::whereHasMorph('testable', [Participant::class], function ($q) use ($company) {
            $q->where('company_id', $company->id);
        })
            ->where('status', 'completed')
            ->with(['test', 'testable'])
            ->latest()
            ->limit(5)
            ->get();
        foreach ($recentSessions as $session) {
            $participant = $session->testable;
            $activities[] = [
                'type' => 'test_completed',
                'title' => "Test '{$session->test->name}' completed by '{$participant->name}'",
                'description' => "Completed at: {$session->completed_at->format('Y-m-d H:i')}",
                'created_at' => $session->completed_at ?? $session->created_at,
            ];
        }

        // Sort by created_at desc and limit to 10
        usort($activities, fn ($a, $b) => $b['created_at'] <=> $a['created_at']);

        return array_slice($activities, 0, 10);
    }

    /**
     * Get growth statistics.
     */
    protected function getGrowthStats(Company $company, Carbon $startOfMonth, Carbon $lastMonthStart, Carbon $lastMonthEnd, Carbon $startOfYear): array
    {
        $participantsThisMonth = Participant::where('company_id', $company->id)
            ->whereBetween('created_at', [$startOfMonth, now()])
            ->count();
        $participantsLastMonth = Participant::where('company_id', $company->id)
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->count();
        $participantsThisYear = Participant::where('company_id', $company->id)
            ->whereBetween('created_at', [$startOfYear, now()])
            ->count();

        $assignmentsThisMonth = TestAssignment::whereHas('participant', function ($q) use ($company) {
            $q->where('company_id', $company->id);
        })
            ->whereBetween('created_at', [$startOfMonth, now()])
            ->count();
        $assignmentsLastMonth = TestAssignment::whereHas('participant', function ($q) use ($company) {
            $q->where('company_id', $company->id);
        })
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->count();

        $sessionsThisMonth = TestSession::whereHasMorph('testable', [Participant::class], function ($q) use ($company) {
            $q->where('company_id', $company->id);
        })
            ->whereBetween('created_at', [$startOfMonth, now()])
            ->count();
        $sessionsLastMonth = TestSession::whereHasMorph('testable', [Participant::class], function ($q) use ($company) {
            $q->where('company_id', $company->id);
        })
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->count();

        return [
            'participants' => [
                'this_month' => $participantsThisMonth,
                'last_month' => $participantsLastMonth,
                'this_year' => $participantsThisYear,
                'growth_percentage' => $participantsLastMonth > 0
                    ? round((($participantsThisMonth - $participantsLastMonth) / $participantsLastMonth) * 100, 2)
                    : ($participantsThisMonth > 0 ? 100 : 0),
            ],
            'test_assignments' => [
                'this_month' => $assignmentsThisMonth,
                'last_month' => $assignmentsLastMonth,
                'growth_percentage' => $assignmentsLastMonth > 0
                    ? round((($assignmentsThisMonth - $assignmentsLastMonth) / $assignmentsLastMonth) * 100, 2)
                    : ($assignmentsThisMonth > 0 ? 100 : 0),
            ],
            'test_sessions' => [
                'this_month' => $sessionsThisMonth,
                'last_month' => $sessionsLastMonth,
                'growth_percentage' => $sessionsLastMonth > 0
                    ? round((($sessionsThisMonth - $sessionsLastMonth) / $sessionsLastMonth) * 100, 2)
                    : ($sessionsThisMonth > 0 ? 100 : 0),
            ],
        ];
    }
}
