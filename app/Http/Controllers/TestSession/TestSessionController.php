<?php

namespace App\Http\Controllers\TestSession;

use App\Http\Controllers\Controller;
use App\Http\Requests\TestSession\SaveAnswersRequest;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use App\Http\Resources\TestSession\TestSessionResource;
use App\Models\TestAssignment;
use App\Models\TestSession;
use App\Services\TestSession\TestSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TestSessionController extends Controller
{
    public function __construct(
        protected TestSessionService $sessionService
    ) {}

    /**
     * Start a new test session.
     */
    public function start(Request $request): JsonResponse
    {
        try {
            $assignment = $request->input('assignment');
            $participant = $request->input('participant');

            $session = $this->sessionService->startSession($assignment, $participant);

            return (new SuccessResource(
                new TestSessionResource($session),
                'Test session started successfully'
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Get session details.
     */
    public function show(string $sessionToken): JsonResponse
    {
        try {
            $session = $this->sessionService->getSession($sessionToken);

            return (new SuccessResource(
                new TestSessionResource($session),
                'Session retrieved successfully'
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 404))->response();
        }
    }

    /**
     * Auto-save answers.
     */
    public function saveAnswers(SaveAnswersRequest $request, string $sessionToken): JsonResponse
    {
        try {
            $session = $this->sessionService->getSession($sessionToken);

            if ($session->status !== 'in_progress') {
                return (new ErrorResource('Session is not in progress', 400))->response();
            }

            $session = $this->sessionService->autoSaveAnswers($session, $request->validated()['answers']);

            return (new SuccessResource(
                new TestSessionResource($session),
                'Answers saved successfully'
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Submit test session.
     */
    public function submit(string $sessionToken): JsonResponse
    {
        try {
            $session = $this->sessionService->getSession($sessionToken);

            if ($session->status !== 'in_progress') {
                return (new ErrorResource('Session is not in progress', 400))->response();
            }

            $session = $this->sessionService->submitSession($session);

            return (new SuccessResource(
                new TestSessionResource($session),
                'Test submitted successfully'
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Update time spent (called periodically from frontend).
     */
    public function updateTime(Request $request, string $sessionToken): JsonResponse
    {
        try {
            $session = $this->sessionService->getSession($sessionToken);

            if ($session->status !== 'in_progress') {
                return (new ErrorResource('Session is not in progress', 400))->response();
            }

            $session = $this->sessionService->updateTimeSpent($session);

            return (new SuccessResource([
                'time_spent_seconds' => $session->time_spent_seconds,
            ], 'Time updated successfully'))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }
}
