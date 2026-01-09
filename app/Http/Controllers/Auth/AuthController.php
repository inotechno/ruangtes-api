<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterCompanyRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use App\Http\Resources\UserResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->registerPublicUser($request->validated());

            return (new SuccessResource(
                new UserResource($user->load(['userable', 'roles'])),
                'Registration successful. Please verify your email.',
                201
            ))->toResponse($request);
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 401))->toResponse($request);
        }
    }

    public function registerCompany(RegisterCompanyRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->registerCompanyWithAdmin($request->validated());

            return (new SuccessResource(
                new UserResource($user->load(['userable', 'roles'])),
                'Registration successful. Please verify your email.',
                201
            ))->toResponse($request);
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 401))->toResponse($request);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->validated());

            return (new SuccessResource([
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ], 'Login successful'))->toResponse($request);
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 401))->toResponse($request);
        }
    }

    public function logout(): JsonResponse
    {
        try {
            $this->authService->logout(Auth::user());

            return (new SuccessResource(null, 'Logout successful'))->toResponse(request());
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->toResponse(request());
        }
    }

    public function me(): JsonResponse
    {
        try {
            $user = Auth::user()->load(['userable', 'roles', 'permissions']);

            return (new SuccessResource(
                new UserResource($user),
                'User data retrieved successfully'
            ))->toResponse(request());
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 400))->toResponse(request());
        }
    }
}
