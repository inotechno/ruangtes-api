<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\ChangePasswordRequest;
use App\Http\Requests\Profile\CompleteProfileRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\Profile\ProfileResource;
use App\Http\Resources\SuccessResource;
use App\Services\Profile\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function __construct(
        protected ProfileService $profileService
    ) {}

    /**
     * Get current user profile.
     */
    public function show(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (! $user->isPublicUser()) {
                return (new ErrorResource('Unauthorized', 403))->response();
            }

            $profile = $this->profileService->getProfile($user);
            $completionStatus = $this->profileService->getProfileCompletionStatus($user);

            return (new SuccessResource([
                'profile' => new ProfileResource($profile),
                'completion_status' => $completionStatus,
            ], 'Profile retrieved successfully'))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Get profile completion status.
     */
    public function completionStatus(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (! $user->isPublicUser()) {
                return (new ErrorResource('Unauthorized', 403))->response();
            }

            $status = $this->profileService->getProfileCompletionStatus($user);

            return (new SuccessResource($status, 'Profile completion status retrieved successfully'))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Complete profile (first time setup).
     */
    public function complete(CompleteProfileRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (! $user->isPublicUser()) {
                return (new ErrorResource('Unauthorized', 403))->response();
            }

            // Check if profile is already complete
            if ($this->profileService->isProfileComplete($user)) {
                return (new ErrorResource('Profile is already complete', 400))->response();
            }

            $profile = $this->profileService->completeProfile($user, $request->validated());

            return (new SuccessResource(
                new ProfileResource($profile),
                'Profile completed successfully',
                201
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Update profile.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (! $user->isPublicUser()) {
                return (new ErrorResource('Unauthorized', 403))->response();
            }

            $profile = $this->profileService->updateProfile($user, $request->validated());

            return (new SuccessResource(
                new ProfileResource($profile),
                'Profile updated successfully'
            ))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }

    /**
     * Change password.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (! $user->isPublicUser()) {
                return (new ErrorResource('Unauthorized', 403))->response();
            }

            $this->profileService->updatePassword(
                $user,
                $request->current_password,
                $request->password
            );

            return (new SuccessResource(null, 'Password changed successfully'))->response();
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->response();
        }
    }
}
