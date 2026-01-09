<?php

namespace App\Services\Participant;

use App\Models\Participant;
use App\Models\TestAssignment;
use Illuminate\Support\Facades\DB;

class ParticipantFlowService
{
    /**
     * Get assignment info by token.
     */
    public function getAssignmentByToken(string $token): TestAssignment
    {
        $assignment = TestAssignment::where('unique_token', $token)
            ->with(['participant', 'test.category'])
            ->firstOrFail();

        return $assignment;
    }

    /**
     * Get all assignments for participant (for multi-test flow).
     */
    public function getParticipantAssignments(Participant $participant): \Illuminate\Database\Eloquent\Collection
    {
        return TestAssignment::where('participant_id', $participant->id)
            ->where('is_completed', false)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->with(['test.category'])
            ->orderBy('start_date')
            ->get();
    }

    /**
     * Check if participant biodata is complete.
     */
    public function isBiodataComplete(Participant $participant): bool
    {
        if (! $participant->biodata) {
            return false;
        }

        $requiredFields = ['name', 'email', 'phone', 'date_of_birth', 'address'];
        $biodata = $participant->biodata;

        foreach ($requiredFields as $field) {
            if (empty($biodata[$field] ?? null)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Complete participant biodata.
     */
    public function completeBiodata(Participant $participant, array $biodata): Participant
    {
        return DB::transaction(function () use ($participant, $biodata) {
            $currentBiodata = $participant->biodata ?? [];
            $updatedBiodata = array_merge($currentBiodata, $biodata);

            $participant->update([
                'biodata' => $updatedBiodata,
            ]);

            return $participant->fresh();
        });
    }

    /**
     * Get test instructions data.
     */
    public function getTestInstructions(TestAssignment $assignment): array
    {
        $test = $assignment->test;
        $participant = $assignment->participant;

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
            'assignment' => [
                'id' => $assignment->id,
                'token' => $assignment->unique_token,
                'start_date' => $assignment->start_date->toIso8601String(),
                'end_date' => $assignment->end_date->toIso8601String(),
                'time_remaining' => now()->diffInSeconds($assignment->end_date, false),
            ],
            'participant' => [
                'name' => $participant->name,
                'email' => $participant->email,
            ],
        ];
    }

    /**
     * Check if assignment has active session.
     */
    public function hasActiveSession(TestAssignment $assignment): bool
    {
        return $assignment->testSessions()
            ->where('status', 'in_progress')
            ->exists();
    }

    /**
     * Get active session for assignment.
     */
    public function getActiveSession(TestAssignment $assignment): ?\App\Models\TestSession
    {
        return $assignment->testSessions()
            ->where('status', 'in_progress')
            ->latest()
            ->first();
    }
}
