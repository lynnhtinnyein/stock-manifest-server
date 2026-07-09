<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthService
{
    private const MAX_LOGIN_ATTEMPTS = 5;

    public function createAccessToken(User $user, bool $remember = false): string
    {
        $lifetime = $remember
            ? config('auth.token_remember_lifetime')
            : config('auth.token_lifetime');
        
        $user_permission_values = $user->permissions->pluck('value')->toArray();

        $token = $user->createToken(
            name: $user->username,
            abilities: $user_permission_values ?? [],
            expiresAt: now()->addSeconds($lifetime)
        );
        return $token->plainTextToken;
    }

    //rate limiting for login attempts
    public function checkRateLimit(Request $request): void
    {
        $key = 'login:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_LOGIN_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => ["Too many login attempts. Please try again in {$seconds} seconds."],
            ])->status(429);
        }
    }

    public function hitRateLimit(Request $request): void
    {
        $key = 'login:' . $request->ip();
        RateLimiter::hit($key, 300);
    }

    public function clearRateLimit(Request $request): void
    {
        $key = 'login:' . $request->ip();
        RateLimiter::clear($key);
    }

    //destory
    public function clearAllExpiredTokens(User $user): void
    {
        $user->tokens()->where('expires_at', '<', now())->delete();
    }

    public function logoutFromAllDevices(User $user): void
    {
        if ($user->currentAccessToken()) {
            $user->tokens()
                ->where('id', '!=', $user->currentAccessToken()->id)
                ->delete();

            return;
        }
        $user->tokens()->delete();
    }

    public function resetPassword(User $user, string $newPassword): void
    {
        DB::transaction(function () use ($user, $newPassword) {
            $user->password = Hash::make($newPassword);
            $user->save();

            $this->logoutFromAllDevices($user);
        });
    }

    //helpers
    public function isCurrentPassword(string $password): bool
    {
        $user = Auth::user();
        return Hash::check($password, $user->password);
    }

    public function getAuthenticatedUser(): ?User
    {
        return Auth::user()?->load('permissions', 'createdBy');
    }
}
