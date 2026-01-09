<?php

namespace App\Services\PublicUser;

use App\Models\Test;
use App\Models\User;
use App\Services\TestSession\TestSessionService;
use App\Services\Transaction\TransactionService;

class PublicUserTestFlowService
{
    public function __construct(
        protected TransactionService $transactionService,
        protected TestSessionService $sessionService
    ) {}

    /**
     * Get test instructions for public user.
     */
    public function getTestInstructions(User $user, int $testId): array
    {
        // Verify user has purchased this test
        if (! $this->transactionService->hasPurchasedTest($user, $testId)) {
            throw new \Exception('Test not purchased or payment not verified');
        }

        $test = Test::with('category')->findOrFail($testId);

        // Check if test is active
        if (! $test->is_active) {
            throw new \Exception('Test is not available');
        }

        // Check if user already completed this test
        $publicUser = $user->userable;
        $completedSession = $publicUser->testSessions()
            ->where('test_id', $testId)
            ->where('status', 'completed')
            ->exists();

        return [
            'test' => [
                'id' => $test->id,
                'name' => $test->name,
                'code' => $test->code,
                'description' => $test->description,
                'duration_minutes' => $test->duration_minutes,
                'question_count' => $test->question_count,
                'category' => $test->category->name ?? null,
            ],
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'is_completed' => $completedSession,
        ];
    }

    /**
     * Check if user can start test (has purchased and payment verified).
     */
    public function canStartTest(User $user, int $testId): bool
    {
        if (! $this->transactionService->hasPurchasedTest($user, $testId)) {
            return false;
        }

        $test = Test::find($testId);
        if (! $test || ! $test->is_active) {
            return false;
        }

        return true;
    }

    /**
     * Start test session for public user.
     */
    public function startTest(User $user, int $testId): \App\Models\TestSession
    {
        if (! $this->canStartTest($user, $testId)) {
            throw new \Exception('Cannot start test. Test not purchased or payment not verified.');
        }

        $test = Test::findOrFail($testId);
        return $this->sessionService->startSessionForPublicUser($test, $user);
    }

    /**
     * Get purchased tests that can be taken.
     */
    public function getAvailableTests(User $user): \Illuminate\Database\Eloquent\Collection
    {
        $purchasedTestIds = $this->transactionService->getPurchasedTestIds($user);

        return Test::whereIn('id', $purchasedTestIds)
            ->where('is_active', true)
            ->with('category')
            ->get()
            ->map(function ($test) use ($user) {
                $publicUser = $user->userable;
                $hasActiveSession = $publicUser->testSessions()
                    ->where('test_id', $test->id)
                    ->where('status', 'in_progress')
                    ->exists();
                $isCompleted = $publicUser->testSessions()
                    ->where('test_id', $test->id)
                    ->where('status', 'completed')
                    ->exists();

                return [
                    'id' => $test->id,
                    'name' => $test->name,
                    'code' => $test->code,
                    'description' => $test->description,
                    'duration_minutes' => $test->duration_minutes,
                    'question_count' => $test->question_count,
                    'category' => $test->category->name ?? null,
                    'has_active_session' => $hasActiveSession,
                    'is_completed' => $isCompleted,
                ];
            });
    }
}
