<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Participant\CompleteBiodataRequest;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\Participant\ParticipantFlowResource;
use App\Http\Resources\SuccessResource;
use App\Models\TestAssignment;
use App\Services\Participant\ParticipantFlowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParticipantFlowController extends Controller
{
    public function __construct(
        protected ParticipantFlowService $flowService
    ) {}

    /**
     * Get assignment info by token (public endpoint, no auth).
     */
    public function getAssignment(string $token): JsonResponse
    {
        try {
            $assignment = $this->flowService->getAssignmentByToken($token);
            
            // Check if participant is banned
            if ($assignment->participant->isBanned()) {
                return (new ErrorResource('You have been banned from taking tests', 403))->response();
            }
            
            // Check if assignment period is valid
            if (now() < $assignment->start_date || now() > $assignment->end_date) {
                return (new ErrorResource('Test assignment period has expired or not yet started', 403))->response();
            }
            
            // Check if already completed
            if ($assignment->is_completed) {
                return (new ErrorResource('Test assignment already completed', 403))->response();
            }

            return (new SuccessResource(
                new ParticipantFlowResource($assignment),
                'Assignment retrieved successfully'
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 404))->response();
        }
    }

    /**
     * Get all assignments for participant (multi-test flow).
     */
    public function getAssignments(Request $request): JsonResponse
    {
        try {
            $participant = $request->input('participant');
            $assignments = $this->flowService->getParticipantAssignments($participant);

            return (new SuccessResource(
                ParticipantFlowResource::collection($assignments),
                'Assignments retrieved successfully'
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Check biodata completion status.
     */
    public function checkBiodata(Request $request): JsonResponse
    {
        try {
            $participant = $request->input('participant');
            $isComplete = $this->flowService->isBiodataComplete($participant);

            return (new SuccessResource([
                'is_complete' => $isComplete,
                'biodata' => $participant->biodata ?? [],
            ], 'Biodata status retrieved successfully'))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Complete participant biodata.
     */
    public function completeBiodata(CompleteBiodataRequest $request): JsonResponse
    {
        try {
            $participant = $request->input('participant');
            $participant = $this->flowService->completeBiodata($participant, $request->validated());

            return (new SuccessResource([
                'participant' => [
                    'id' => $participant->id,
                    'name' => $participant->name,
                    'email' => $participant->email,
                    'biodata' => $participant->biodata,
                ],
                'is_complete' => $this->flowService->isBiodataComplete($participant),
            ], 'Biodata completed successfully'))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Get test instructions.
     */
    public function getInstructions(Request $request): JsonResponse
    {
        try {
            $assignment = $request->input('assignment');
            $instructions = $this->flowService->getTestInstructions($assignment);

            return (new SuccessResource($instructions, 'Test instructions retrieved successfully'))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }
}
