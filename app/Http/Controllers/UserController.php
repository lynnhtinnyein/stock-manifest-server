<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Services\UserService;
use App\Services\AuthService;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\User\IndexUserRequest;
use App\Http\Requests\User\CreateUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\User\UpdatePasswordRequest;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService,
        protected AuthService $authService
    ) {}

    //helper
    private function buildIndexQueryParams(Request $request, bool $only_trashed): array
    {
        return [
            'filters' => [
                'search' => $request->get('search'),
                'status' => $request->get('status', 'ALL'),
                'role' => $request->get('role', 'ALL'),
                'onlyTrashed' => $only_trashed
            ],
            'sorting' => [
                'sortBy' => $request->get('sortBy', 'created_at'),
                'sortOrder' => strtoupper($request->get('sortOrder', 'DESC'))
            ],
            'authUser' => $request->user(),
            'perPage' => (int) $request->get('perPage', 15)
        ];
    }

    public function index(IndexUserRequest $request): JsonResponse
    {
        try {
            $users = $this->userService->getAll(
                $this->buildIndexQueryParams($request, false)
            );

            return response()->json(
                UserResource::collection($users),
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            Log::error('Error fetching users', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while fetching users.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function trashed(IndexUserRequest $request): JsonResponse
    {
        try {
            $trashedUsers = $this->userService->getAll(
                $this->buildIndexQueryParams($request, true)
            );

            return response()->json(
                UserResource::collection($trashedUsers),
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            Log::error('Error fetching trashed users', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while fetching trashed users.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Int $id): JsonResponse
    {
        try {
            $user = $this->userService->getById($id);
            return response()->json(new UserResource($user), Response::HTTP_OK);
        } catch (\Throwable $th) {
            Log::error('Error fetching user', [
                'user_id' => $id,
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while fetching user details.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(CreateUserRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = $this->userService->create(
                $request->validated(),
                $request->input('permissionIds', [])
            );

            DB::commit();

            $userWithDetails = $this->userService->getById($user->id);

            return response()->json(new UserResource($userWithDetails), Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            DB::rollBack();

            Log::error('User creation error occurred', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'username' => $request->input('username', 'N/A'),
                'admin_id' => Auth::user()->id,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        try {
            $updatedUser = $this->userService->update(
                $user,
                $request->validated(),
                $request->input('permissionIds', [])
            );

            $userWithDetails = $this->userService->getById($updatedUser->id);

            return response()->json(new UserResource($userWithDetails), Response::HTTP_OK);
        } catch (\Throwable $th) {
            Log::error('Error updating user', [
                'user_id' => $user->id,
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while updating user.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updatePassword(UpdatePasswordRequest $request, User $user): JsonResponse
    {
        try {
            $this->authService->resetPassword($user, $request->password);

            return response()->json([
                'status' => true,
                'message' => 'Password has been updated successfully. User has been logged out from all devices.',
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Log::error('Error updating password by admin', [
                'admin_id' => $request->user()->id,
                'target_user_id' => $user->id,
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while updating password.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function suspend(Request $request, User $user): JsonResponse
    {
        try {
            if ($user->isSuspended()) {
                $userWithDetails = $this->userService->getById($user->id);
                return response()->json(new UserResource($userWithDetails), Response::HTTP_OK);
            }

            if ($user->id === $request->user()->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'You cannot suspend your own account.',
                ], Response::HTTP_FORBIDDEN);
            }

            $updatedUser = $this->userService->toggleAccountSuspension($user, $request->user()->id, true);

            $this->authService->logoutFromAllDevices($user);

            $userWithDetails = $this->userService->getById($updatedUser->id);

            return response()->json(new UserResource($userWithDetails), Response::HTTP_OK);
        } catch (\Throwable $th) {
            Log::error('Error suspending user', [
                'user_id' => $user->id,
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while suspending user.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function restore(Request $request, User $user): JsonResponse
    {
        try {
            if ($user->isActive()) {
                $userWithDetails = $this->userService->getById($user->id);
                return response()->json(new UserResource($userWithDetails), Response::HTTP_OK);
            }

            $updatedUser = $this->userService->toggleAccountSuspension($user, $request->user()->id, false);
            $userWithDetails = $this->userService->getById($updatedUser->id);

            return response()->json(new UserResource($userWithDetails), Response::HTTP_OK);
        } catch (\Throwable $th) {
            Log::error('Error restoring user', [
                'user_id' => $user->id,
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while restoring user access.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Int $id): JsonResponse
    {
        try {
            $this->userService->delete($id);
            return response()->json($id, Response::HTTP_OK);
        } catch (\Throwable $th) {
            Log::error('Error deleting user', [
                'user_id' => $id,
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while deleting user.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function restoreFromTrash(Int $id): JsonResponse
    {
        try {
            $this->userService->restoreDeleted($id);
            $userWithDetails = $this->userService->getById($id);
            return response()->json(new UserResource($userWithDetails), Response::HTTP_OK);
        } catch (\Throwable $th) {
            Log::error('Error restoring user', [
                'user_id' => $id,
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while restoring user.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function forceDelete(Int $id): JsonResponse
    {
        try {
            $this->userService->forceDelete($id);

            return response()->json($id, Response::HTTP_NO_CONTENT);
        } catch (\Throwable $th) {
            Log::error('Error force deleting user', [
                'user_id' => $id,
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while permanently deleting user.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
