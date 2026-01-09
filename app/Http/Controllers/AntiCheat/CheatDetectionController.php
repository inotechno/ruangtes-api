<?php

namespace App\Http\Controllers\AntiCheat;

use App\Enums\CheatDetectionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\AntiCheat\LogCheatEventRequest;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use App\Models\TestSession;
use App\Services\AntiCheat\AntiCheatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheatDetectionController extends Controller
{
    public function __construct(
        protected AntiCheatService $antiCheatService
    ) {}

    /**
     * Log a cheat event from frontend.
     */
    public function logEvent(LogCheatEventRequest $request, string $sessionToken): JsonResponse
    {
        try {
            $session = TestSession::where('session_token', $sessionToken)
                ->where('status', 'in_progress')
                ->firstOrFail();

            $type = CheatDetectionType::from($request->detection_type);
            $detection = $this->antiCheatService->logCheatEvent(
                $session,
                $type,
                $request->detection_data ?? [],
                $request->severity ?? 1
            );

            return (new SuccessResource([
                'detection' => [
                    'id' => $detection->id,
                    'type' => $detection->detection_type->value,
                    'severity' => $detection->severity,
                    'created_at' => $detection->created_at->toIso8601String(),
                ],
                'session_status' => $session->fresh()->status,
            ], 'Cheat event logged successfully'))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Get cheat detections for session.
     */
    public function getDetections(string $sessionToken): JsonResponse
    {
        try {
            $session = TestSession::where('session_token', $sessionToken)->firstOrFail();
            $detections = $this->antiCheatService->getDetections($session);

            return (new SuccessResource($detections, 'Detections retrieved successfully'))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 404))->response();
        }
    }
}
