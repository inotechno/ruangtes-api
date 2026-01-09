<?php

namespace App\Services\AntiCheat;

use App\Enums\CheatDetectionType;
use App\Models\CheatDetection;
use App\Models\TestSession;
use App\Models\TestSessionEvent;
use Illuminate\Support\Facades\DB;

class AntiCheatService
{
    /**
     * Log a cheat event.
     */
    public function logCheatEvent(TestSession $session, CheatDetectionType $type, array $data = [], int $severity = 1): CheatDetection
    {
        return DB::transaction(function () use ($session, $type, $data, $severity) {
            // Log event
            TestSessionEvent::create([
                'test_session_id' => $session->id,
                'event_type' => $type->value,
                'event_data' => $data,
                'occurred_at' => now(),
            ]);

            // Create cheat detection
            $detection = CheatDetection::create([
                'test_session_id' => $session->id,
                'detection_type' => $type,
                'detection_data' => $data,
                'severity' => $severity,
                'is_resolved' => false,
            ]);

            // Auto-ban if severity is high (5) or multiple detections
            $this->checkAutoBan($session, $detection);

            return $detection;
        });
    }

    /**
     * Check if session should be auto-banned.
     */
    protected function checkAutoBan(TestSession $session, CheatDetection $detection): void
    {
        // Auto-ban if severity is maximum (5)
        if ($detection->severity >= 5) {
            $this->banSession($session, 'High severity cheat detection');
            return;
        }

        // Auto-ban if multiple detections of same type
        $sameTypeCount = CheatDetection::where('test_session_id', $session->id)
            ->where('detection_type', $detection->detection_type)
            ->where('is_resolved', false)
            ->count();

        if ($sameTypeCount >= 3) {
            $this->banSession($session, 'Multiple cheat detections of same type');
            return;
        }

        // Auto-ban if total detections exceed threshold
        $totalDetections = CheatDetection::where('test_session_id', $session->id)
            ->where('is_resolved', false)
            ->count();

        if ($totalDetections >= 10) {
            $this->banSession($session, 'Excessive cheat detections');
        }
    }

    /**
     * Ban a test session.
     */
    public function banSession(TestSession $session, string $reason): TestSession
    {
        $session->update([
            'status' => 'banned',
            'metadata' => array_merge($session->metadata ?? [], [
                'banned_at' => now()->toIso8601String(),
                'ban_reason' => $reason,
            ]),
        ]);

        // Also ban participant if applicable
        if ($session->testable_type === \App\Models\Participant::class) {
            $participant = $session->testable;
            if ($participant && ! $participant->isBanned()) {
                $participant->update(['banned_at' => now()]);
            }
        }

        return $session->fresh();
    }

    /**
     * Detect time anomaly (too fast or too slow).
     */
    public function detectTimeAnomaly(TestSession $session): ?CheatDetection
    {
        $test = $session->test;
        $timeSpent = $session->time_spent_seconds;
        $expectedTime = $test->duration_minutes * 60;
        $expectedMinTime = $expectedTime * 0.3; // At least 30% of expected time
        $expectedMaxTime = $expectedTime * 1.5; // At most 150% of expected time

        if ($timeSpent < $expectedMinTime && $session->answers()->count() >= $test->question_count) {
            // Completed too fast
            return $this->logCheatEvent(
                $session,
                CheatDetectionType::TimeAnomaly,
                [
                    'time_spent' => $timeSpent,
                    'expected_min_time' => $expectedMinTime,
                    'anomaly_type' => 'too_fast',
                ],
                3
            );
        }

        if ($timeSpent > $expectedMaxTime) {
            // Taking too long (might be using external help)
            return $this->logCheatEvent(
                $session,
                CheatDetectionType::TimeAnomaly,
                [
                    'time_spent' => $timeSpent,
                    'expected_max_time' => $expectedMaxTime,
                    'anomaly_type' => 'too_slow',
                ],
                2
            );
        }

        return null;
    }

    /**
     * Get cheat detections for session.
     */
    public function getDetections(TestSession $session): \Illuminate\Database\Eloquent\Collection
    {
        return CheatDetection::where('test_session_id', $session->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
