<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\RoleService;
use Symfony\Component\HttpFoundation\Response;

class CheckRoleAccess
{
    public function __construct(private RoleService $roles) {}

    public function handle(Request $request, Closure $next): Response
    {
        $authUser = $request->user();

        if (!$authUser) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Check target user access
        $targetUser = $this->getTargetUser($request);

        if ($targetUser && !$this->roles->canAccess($authUser, $targetUser)) {
            return response()->json([
                'message' => 'You cannot access this account.'
            ], 403);
        }

        // Check role in request body
        if ($request->has('role')) {
            $requestedRole = $request->input('role');
            $action = $request->route()->getActionMethod();

            try {
                $this->roles->validate($authUser->role, $requestedRole, $action);
            } catch (\Exception $e) {
                return response()->json(['message' => $e->getMessage()], 403);
            }
        }

        return $next($request);
    }

    private function getTargetUser(Request $request): ?User
    {
        $userId = $request->route('user') ?? $request->route('id');

        if ($userId instanceof User) return $userId;
        if (is_numeric($userId)) return User::withTrashed()->find($userId);

        return null;
    }
}
