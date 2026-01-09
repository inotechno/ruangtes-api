<?php

namespace App\Http\Controllers\PublicUser;

use App\Http\Controllers\Controller;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use App\Services\PublicUser\PublicUserTestFlowService;
use App\Services\TestSession\TestSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PublicUserTestFlowController extends Controller
{
    public function __construct(
        protected PublicUserTestFlowService $flowService,
        protected TestSessionService $sessionService
    ) {}

    /**
     * Get available tests (purchased and payment verified).
     */
    public function getAvailableTests(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (! $user->isPublicUser()) {
                return (new ErrorResource('Unauthorized', 403))->response();
            }

            $tests = $this->flowService->getAvailableTests($user);

            return (new SuccessResource($tests, 'Available tests retrieved successfully'))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Get test instructions.
     */
    public function getInstructions(int $testId): JsonResponse
    {
        try {
            $user = Auth::user();

            if (! $user->isPublicUser()) {
                return (new ErrorResource('Unauthorized', 403))->response();
            }

            $instructions = $this->flowService->getTestInstructions($user, $testId);

            return (new SuccessResource($instructions, 'Test instructions retrieved successfully'))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Start test session.
     */
    public function startTest(int $testId): JsonResponse
    {
        try {
            $user = Auth::user();

            if (! $user->isPublicUser()) {
                return (new ErrorResource('Unauthorized', 403))->response();
            }

            $session = $this->flowService->startTest($user, $testId);

            return (new SuccessResource([
                'session' => [
                    'session_token' => $session->session_token,
                    'test' => [
                        'id' => $session->test->id,
                        'name' => $session->test->name,
                        'duration_minutes' => $session->test->duration_minutes,
                    ],
                    'started_at' => $session->started_at->toIso8601String(),
                ],
            ], 'Test session started successfully'))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }
}
