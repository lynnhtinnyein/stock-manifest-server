<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSuspended
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->is_suspended) {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'status' => false,
                'message' => 'Your account has been suspended. Please contact support.',
                'suspended' => true,
            ], 423); // 423 Locked
        }

        return $next($request);
    }
}
