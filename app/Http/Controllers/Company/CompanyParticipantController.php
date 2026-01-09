<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Resources\Company\ParticipantResource;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use App\Models\Participant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyParticipantController extends Controller
{
    /**
     * Display a listing of participants for a company.
     */
    public function index(int $companyId): JsonResponse
    {
        try {
            $participants = Participant::where('company_id', $companyId)
                ->withCount('testAssignments')
                ->orderBy('created_at', 'desc')
                ->paginate(request('per_page', 15));

            return (new SuccessResource(
                ParticipantResource::collection($participants)->response()->getData(true),
                'Company participants retrieved successfully'
            ))->toResponse(request());
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 500))->toResponse(request());
        }
    }

    /**
     * Display the specified participant.
     */
    public function show(int $companyId, int $participantId): JsonResponse
    {
        try {
            $participant = Participant::where('company_id', $companyId)
                ->with(['testAssignments', 'testSessions'])
                ->findOrFail($participantId);

            return (new SuccessResource(
                new ParticipantResource($participant),
                'Participant retrieved successfully'
            ))->toResponse(request());
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 404))->toResponse(request());
        }
    }
}
