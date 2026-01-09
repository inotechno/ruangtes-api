<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use App\Services\Auth\EmailVerificationService;
use Illuminate\Http\JsonResponse;

class EmailVerificationController extends Controller
{
    public function __construct(
        protected EmailVerificationService $emailVerificationService
    ) {
    }

    public function verify(VerifyEmailRequest $request): JsonResponse
    {
        try {
            $user = $this->emailVerificationService->verifyEmail($request->validated()['token']);

            return (new SuccessResource(
                ['user' => $user],
                'Email verified successfully'
            ))->toResponse($request);
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->toResponse($request);
        }
    }

    public function resend(): JsonResponse
    {
        try {
            $user = auth()->user();

            if (! $user) {
                return (new ErrorResource('User not authenticated', 401))->toResponse(request());
            }

            $this->emailVerificationService->resendVerificationEmail($user);

            return (new SuccessResource(
                null,
                'Verification email sent successfully'
            ))->toResponse(request());
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->toResponse(request());
        }
    }
}
