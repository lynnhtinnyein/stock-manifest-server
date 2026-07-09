<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AuthService;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\Auth\ChangePasswordRequest;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    public function check(): JsonResponse
    {
        return response()->json(['authenticated' => true], Response::HTTP_OK);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $this->authService->checkRateLimit($request);

            if (!Auth::attempt($request->only('username', 'password'))) {
                $this->authService->hitRateLimit($request);

                return response()->json([
                    'status' => false,
                    'message' => 'Invalid credentials. Please check your username and password.',
                    'errors' => [
                        'credentials' => ['Username or password is incorrect.']
                    ]
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $user = Auth::user();

            if ($user->is_suspended) {
                Auth::logout();
                throw ValidationException::withMessages([
                    'account' => ['Your account has been suspended. Please contact support.']
                ])->status(423);
            }

            if ($user->permissions->isEmpty()) {
                Auth::logout();
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to access this application.',
                    'errors' => [
                        'permissions' => ['No permissions have been assigned to your account.']
                    ]
                ], Response::HTTP_FORBIDDEN);
            }

            $this->authService->clearRateLimit($request);
            $this->authService->clearAllExpiredTokens($user);

            $access_token = $this->authService->createAccessToken($user, $request->boolean('remember'));

            return response()->json([
                'accessToken' => $access_token,
                'user' => new UserResource($user),
            ], Response::HTTP_OK);
        } catch (ValidationException $e) {
            if ($e->status === 423) {
                return response()->json([
                    'status' => false,
                    'message' => 'Your account has been suspended. Please contact support.',
                    'suspended' => true,
                ], Response::HTTP_LOCKED);
            }

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], $e->status);
        } catch (\Throwable $th) {
            Log::error('Login error occurred', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred during login. Please try again later.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Logged out successfully.',
        ], Response::HTTP_OK);
    }

    public function me(): JsonResponse
    {
        $user = $this->authService->getAuthenticatedUser();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return response()->json(new UserResource($user), Response::HTTP_OK);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        try {
            if (!$this->authService->isCurrentPassword($request->currentPassword)) {
                return response()->json([
                    'status' => false,
                    'message' => 'The current password is incorrect.',
                    'errors' => [
                        'currentPassword' => ['The current password is incorrect.']
                    ]
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($this->authService->isCurrentPassword($request->newPassword)) {
                return response()->json([
                    'status' => false,
                    'message' => 'The new password must be different from your current password.',
                    'errors' => [
                        'newPassword' => ['The new password must be different from your current password.']
                    ]
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $this->authService->resetPassword($request->user(), $request->newPassword);

            return response()->json([
                'status' => true,
                'message' => 'Your password changed successfully.',
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Log::error('Error changing password', [
                'user_id' => $request->user()?->id,
                'username' => $request->user()?->username,
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while changing password. Please try again later.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
