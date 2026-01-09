<?php

namespace App\Services\TestSession;

use App\Models\Participant;
use App\Models\PublicUser;
use App\Models\Test;
use App\Models\TestAssignment;
use App\Models\TestSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TestSessionService
{
    /**
     * Start a new test session for participant (with assignment).
     */
    public function startSession(TestAssignment $assignment, Participant $participant): TestSession
    {
        return DB::transaction(function () use ($assignment, $participant) {
            // Check if there's already an active session
            $activeSession = $assignment->testSessions()
                ->where('status', 'in_progress')
                ->first();

            if ($activeSession) {
                return $activeSession;
            }

            // Create new session
            $sessionToken = 'SESSION-'.strtoupper(Str::random(16));

            $session = TestSession::create([
                'testable_type' => Participant::class,
                'testable_id' => $participant->id,
                'test_id' => $assignment->test_id,
                'test_assignment_id' => $assignment->id,
                'session_token' => $sessionToken,
                'started_at' => now(),
                'status' => 'in_progress',
                'time_spent_seconds' => 0,
                'metadata' => [
                    'started_at' => now()->toIso8601String(),
                    'test_duration_minutes' => $assignment->test->duration_minutes,
                ],
            ]);

            return $session->load(['test', 'testAssignment']);
        });
    }

    /**
     * Start a new test session for public user (without assignment).
     */
    public function startSessionForPublicUser(Test $test, User $user): TestSession
    {
        return DB::transaction(function () use ($test, $user) {
            $publicUser = $user->userable;

            if (! $publicUser instanceof PublicUser) {
                throw new \Exception('User is not a public user');
            }

            // Check if there's already an active session for this test
            $activeSession = $publicUser->testSessions()
                ->where('test_id', $test->id)
                ->where('status', 'in_progress')
                ->first();

            if ($activeSession) {
                return $activeSession;
            }

            // Create new session
            $sessionToken = 'SESSION-'.strtoupper(Str::random(16));

            $session = TestSession::create([
                'testable_type' => PublicUser::class,
                'testable_id' => $publicUser->id,
                'test_id' => $test->id,
                'test_assignment_id' => null, // Public user doesn't have assignment
                'session_token' => $sessionToken,
                'started_at' => now(),
                'status' => 'in_progress',
                'time_spent_seconds' => 0,
                'metadata' => [
                    'started_at' => now()->toIso8601String(),
                    'test_duration_minutes' => $test->duration_minutes,
                ],
            ]);

            return $session->load(['test']);
        });
    }

    /**
     * Auto-save answers.
     */
    public function autoSaveAnswers(TestSession $session, array $answers): TestSession
    {
        return DB::transaction(function () use ($session, $answers) {
            foreach ($answers as $questionId => $answer) {
                $session->answers()->updateOrCreate(
                    ['question_id' => $questionId],
                    [
                        'answer' => $answer,
                        'updated_at' => now(),
                    ]
                );
            }

            // Update time spent
            $timeSpent = now()->diffInSeconds($session->started_at);
            $session->update([
                'time_spent_seconds' => $timeSpent,
                'metadata' => array_merge($session->metadata ?? [], [
                    'last_saved_at' => now()->toIso8601String(),
                ]),
            ]);

            return $session->fresh(['answers']);
        });
    }

    /**
     * Submit test session.
     */
    public function submitSession(TestSession $session): TestSession
    {
        return DB::transaction(function () use ($session) {
            if ($session->status !== 'in_progress') {
                throw new \Exception('Session is not in progress');
            }

            $timeSpent = now()->diffInSeconds($session->started_at);

            $session->update([
                'status' => 'completed',
                'completed_at' => now(),
                'time_spent_seconds' => $timeSpent,
                'metadata' => array_merge($session->metadata ?? [], [
                    'submitted_at' => now()->toIso8601String(),
                ]),
            ]);

            // Mark assignment as completed (only for participant)
            if ($session->testAssignment) {
                $session->testAssignment->update([
                    'is_completed' => true,
                    'completed_at' => now(),
                ]);
            }

            return $session->fresh(['answers', 'test', 'testAssignment']);
        });
    }

    /**
     * Get session with answers.
     */
    public function getSession(string $sessionToken): TestSession
    {
        return TestSession::where('session_token', $sessionToken)
            ->with(['answers', 'test', 'testAssignment.participant', 'testable'])
            ->firstOrFail();
    }

    /**
     * Update time spent (called periodically).
     */
    public function updateTimeSpent(TestSession $session): TestSession
    {
        $timeSpent = now()->diffInSeconds($session->started_at);
        
        $session->update([
            'time_spent_seconds' => $timeSpent,
        ]);

        return $session->fresh();
    }
}
