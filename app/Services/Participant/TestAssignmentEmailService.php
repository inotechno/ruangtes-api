<?php

namespace App\Services\Participant;

use App\Mail\TestAssignmentMail;
use App\Models\Participant;
use App\Models\TestAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class TestAssignmentEmailService
{
    /**
     * Send test assignment email to participant.
     */
    public function sendAssignmentEmail(TestAssignment $assignment): bool
    {
        try {
            $participant = $assignment->participant;
            $test = $assignment->test;

            if (! $participant || ! $test) {
                return false;
            }

            // Don't send email if participant is banned
            if ($participant->isBanned()) {
                return false;
            }

            Mail::to($participant->email)->send(
                new TestAssignmentMail($participant, $assignment, $test)
            );

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to send test assignment email: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Send test assignment emails for multiple assignments (batch).
     */
    public function sendAssignmentEmails(iterable $assignments): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($assignments as $assignment) {
            try {
                if ($this->sendAssignmentEmail($assignment)) {
                    $results['sent']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = [
                        'assignment_id' => $assignment->id,
                        'participant_email' => $assignment->participant->email ?? null,
                        'error' => 'Failed to send email',
                    ];
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'assignment_id' => $assignment->id,
                    'participant_email' => $assignment->participant->email ?? null,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Send all assignment emails for a participant.
     */
    public function sendAllAssignmentsForParticipant(Participant $participant): array
    {
        $assignments = $participant->testAssignments()
            ->with(['test'])
            ->whereNull('is_completed')
            ->where('end_date', '>=', now())
            ->get();

        return $this->sendAssignmentEmails($assignments);
    }

    /**
     * Resend assignment email for a specific assignment.
     */
    public function resendAssignmentEmail(TestAssignment $assignment): bool
    {
        return $this->sendAssignmentEmail($assignment);
    }
}
