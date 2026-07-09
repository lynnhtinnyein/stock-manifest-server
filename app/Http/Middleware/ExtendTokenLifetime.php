<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExtendTokenLifetime
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() === 200 && $request->user()) {
            $token = $request->user()->currentAccessToken();

            if ($token && $this->isExtendable($token)) {
                $token_lifetime = config('auth.token_lifetime');
                $token->expires_at = now()->addSeconds($token_lifetime);
                $token->save();
            }
        }

        return $response;
    }

    protected function isExtendable($token): bool
    {
        if (!$token->expires_at) {
            return false;
        }

        $token_lifetime = config('auth.token_lifetime');
        $maxExtendableExpiration = now()->addSeconds($token_lifetime);

        return $token->expires_at->lessThanOrEqualTo($maxExtendableExpiration);
    }
}
