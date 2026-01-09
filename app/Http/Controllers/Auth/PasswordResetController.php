<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use App\Services\Auth\PasswordResetService;
use Illuminate\Http\JsonResponse;

class PasswordResetController extends Controller
{
    public function __construct(
        protected PasswordResetService $passwordResetService
    ) {
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $this->passwordResetService->sendResetLink($request->validated()['email']);

            return (new SuccessResource(
                null,
                'Password reset link sent to your email'
            ))->toResponse($request);
        } catch (\Exception $e) {
            return (new ErrorResource(
                $e->getMessage(),
                400
            ))->toResponse($request);
        }
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $this->passwordResetService->resetPassword(
                $data['email'],
                $data['token'],
                $data['password']
            );

            return (new SuccessResource(
                null,
                'Password reset successfully'
            ))->toResponse($request);
        } catch (\Exception $e) {
            return (new ErrorResource(
                $e->getMessage(),
                400
            ))->toResponse($request);
        }
    }
}
