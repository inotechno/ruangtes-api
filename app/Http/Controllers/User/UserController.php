<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\CreateUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\SuccessResource;
use App\Http\Resources\ErrorResource;
use App\Models\User;
use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(protected UserService $userService) {}

    /**
     * Display a listing of users.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $users = $this->userService->getAllUsers($request->all());

            return (new SuccessResource(
                UserResource::collection($users)->response()->getData(true),
                'Users retrieved successfully'
            ))->toResponse($request);
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 500))->toResponse($request);
        }
    }

    /**
     * Store a newly created user.
     */
    public function store(CreateUserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->createUser($request->validated());

            return (new SuccessResource(
                new UserResource($user),
                'User created successfully',
                statusCode: 201
            ))->toResponse($request);
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 500))->toResponse($request);
        }
    }

    /**
     * Display the specified user.
     */
    public function show(int $id, Request $request): JsonResponse
    {
        try {
            $user = $this->userService->getUserById($id);

            return (new SuccessResource(
                new UserResource($user),
                'User retrieved successfully'
            ))->toResponse($request);
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 500))->toResponse($request);
        }
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        try {
            $updatedUser = $this->userService->updateUser($user, $request->validated());

            return (new SuccessResource(
                new UserResource($updatedUser),
                'User updated successfully'
            ))->toResponse($request);
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 500))->toResponse($request);
        }
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user, Request $request): JsonResponse
    {
        try {
            $this->userService->deleteUser($user);

            return (new SuccessResource(
                null,
                'User deleted successfully'
            ))->toResponse($request);
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage(), 500))->toResponse($request);
        }
    }
}