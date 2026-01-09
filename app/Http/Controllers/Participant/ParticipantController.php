<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Participant\CreateParticipantRequest;
use App\Http\Requests\Participant\ImportParticipantsRequest;
use App\Http\Requests\Participant\UpdateParticipantRequest;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\Participant\ParticipantDetailResource;
use App\Http\Resources\Participant\ParticipantResource;
use App\Http\Resources\SuccessResource;
use App\Models\Participant;
use App\Services\Participant\ParticipantService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParticipantController extends Controller
{
    public function __construct(
        protected ParticipantService $participantService
    ) {}

    /**
     * Get list of participants.
     * - TenantAdmin: only participants from their company
     * - SuperAdmin: all participants (optional filter by company_id)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if ($user->isSuperAdmin()) {
                // SuperAdmin can view all participants, optionally filtered by company
                $participants = $this->participantService->getAllParticipants(
                    $request->integer('company_id'),
                    $request->input('search'),
                    $request->boolean('banned'),
                    $request->input('per_page', 15)
                );
            } elseif ($user->isTenantAdmin()) {
                // TenantAdmin can only view participants from their company
                $companyAdmin = $user->companyAdmin();

                if (! $companyAdmin) {
                    return (new ErrorResource('Company admin not found', 403))->response();
                }

                $company = $companyAdmin->company;
                $participants = $this->participantService->getParticipantsForCompany(
                    $company,
                    $request->input('search'),
                    $request->boolean('banned'),
                    $request->input('per_page', 15)
                );
            } else {
                return (new ErrorResource('Unauthorized', 403))->response();
            }

            return (new SuccessResource(
                ParticipantResource::collection($participants)
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Create participant with test assignments.
     */
    public function store(CreateParticipantRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $companyAdmin = $user->companyAdmin();

            if (! $companyAdmin) {
                return (new ErrorResource('Company admin not found', 403))->response();
            }

            $company = $companyAdmin->company;

            $participant = $this->participantService->createParticipantWithAssignments(
                $company,
                $request->name,
                $request->email,
                $request->test_ids,
                Carbon::parse($request->start_date),
                Carbon::parse($request->end_date)
            );

            return (new SuccessResource(
                new ParticipantDetailResource($participant),
                'Participant created successfully with test assignments.',
                201
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Get participant detail.
     * - TenantAdmin: only participants from their company
     * - SuperAdmin: any participant
     */
    public function show(Participant $participant): JsonResponse
    {
        try {
            $user = Auth::user();

            if ($user->isSuperAdmin()) {
                // SuperAdmin can view any participant
                $participant = $this->participantService->getParticipantDetail($participant);
            } elseif ($user->isTenantAdmin()) {
                // TenantAdmin can only view participants from their company
                $companyAdmin = $user->companyAdmin();

                if (! $companyAdmin) {
                    return (new ErrorResource('Company admin not found', 403))->response();
                }

                // Verify participant belongs to company
                if ($participant->company_id !== $companyAdmin->company_id) {
                    return (new ErrorResource('Participant not found', 404))->response();
                }

                $participant = $this->participantService->getParticipantDetail($participant);
            } else {
                return (new ErrorResource('Unauthorized', 403))->response();
            }

            return (new SuccessResource(
                new ParticipantDetailResource($participant)
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Update participant.
     */
    public function update(UpdateParticipantRequest $request, Participant $participant): JsonResponse
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

            $participant = $this->participantService->updateParticipant(
                $participant,
                $request->input('name'),
                $request->input('email')
            );

            return (new SuccessResource(
                new ParticipantResource($participant),
                'Participant updated successfully.'
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Delete participant.
     */
    public function destroy(Participant $participant): JsonResponse
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

            $this->participantService->deleteParticipant($participant);

            return (new SuccessResource(
                null,
                'Participant deleted successfully.'
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Preview Excel import data.
     */
    public function previewImport(ImportParticipantsRequest $request): JsonResponse
    {
        try {
            $file = $request->file('file');
            $preview = $this->participantService->previewImportData($file);

            return (new SuccessResource($preview))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Import participants from Excel with test assignments.
     */
    public function import(ImportParticipantsRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $companyAdmin = $user->companyAdmin();

            if (! $companyAdmin) {
                return (new ErrorResource('Company admin not found', 403))->response();
            }

            $company = $companyAdmin->company;

            // First, preview the data
            $file = $request->file('file');
            $preview = $this->participantService->previewImportData($file);

            if (empty($preview['valid'])) {
                return (new ErrorResource(
                    'No valid participants found in file. Please check the file format.',
                    400
                ))->response();
            }

            // Import valid participants
            $result = $this->participantService->importParticipantsWithAssignments(
                $company,
                $preview['valid'],
                $request->test_ids,
                Carbon::parse($request->start_date),
                Carbon::parse($request->end_date)
            );

            return (new SuccessResource(
                $result,
                "Import completed. {$result['success']} participants created, {$result['failed_count']} failed."
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Ban participant.
     */
    public function ban(Request $request, Participant $participant): JsonResponse
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

            $participant = $this->participantService->banParticipant(
                $participant,
                $request->input('reason')
            );

            return (new SuccessResource(
                new ParticipantResource($participant),
                'Participant banned successfully.'
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Unban participant.
     */
    public function unban(Participant $participant): JsonResponse
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

            $participant = $this->participantService->unbanParticipant($participant);

            return (new SuccessResource(
                new ParticipantResource($participant),
                'Participant unbanned successfully.'
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }
}
