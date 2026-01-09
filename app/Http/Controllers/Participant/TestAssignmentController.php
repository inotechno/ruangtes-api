<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\Participant\TestAssignmentResource;
use App\Http\Resources\SuccessResource;
use App\Models\Participant;
use App\Models\TestAssignment;
use App\Services\Participant\TestAssignmentEmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TestAssignmentController extends Controller
{
    public function __construct(
        protected TestAssignmentEmailService $emailService
    ) {}

    /**
     * Resend test assignment email for a specific assignment.
     */
    public function resendEmail(TestAssignment $assignment): JsonResponse
    {
        try {
            $user = Auth::user();
            $companyAdmin = $user->companyAdmin();

            if (! $companyAdmin) {
                return (new ErrorResource('Company admin not found', 403))->response();
            }

            // Verify assignment belongs to company
            $participant = $assignment->participant;
            if ($participant->company_id !== $companyAdmin->company_id) {
                return (new ErrorResource('Assignment not found', 404))->response();
            }

            $sent = $this->emailService->resendAssignmentEmail($assignment);

            if ($sent) {
                return (new SuccessResource(
                    new TestAssignmentResource($assignment->load(['test', 'participant'])),
                    'Test assignment email sent successfully.'
                ))->response();
            }

            return (new ErrorResource('Failed to send email', 400))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Resend all assignment emails for a participant.
     */
    public function resendAllEmails(Participant $participant): JsonResponse
    {
        try {
            $user = Auth::user();
            $companyAdmin = $user->companyAdmin();

            if (! $companyAdmin) {
                return (new ErrorResource('Company admin not found', 403))->response();
            }

            // Verify participant belongs to company
            if ($participant->company_id !== $companyAdmin->company_id) {
                return (new ErrorResource('Participant not found', 404))->response();
            }

            $result = $this->emailService->sendAllAssignmentsForParticipant($participant);

            return (new SuccessResource(
                $result,
                "Email sending completed. {$result['sent']} sent, {$result['failed']} failed."
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }
}
