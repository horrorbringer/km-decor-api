<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirect(string $provider): JsonResponse
    {
        abort_unless(in_array($provider, ['google']), 400, 'Invalid provider');

        $url = Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();

        return response()->json(['url' => $url]);
    }

    public function callback(string $provider, Request $request): JsonResponse
    {
        abort_unless(in_array($provider, ['google']), 400, 'Invalid provider');

        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
        } catch (\Exception $e) {
            $frontendUrl = rtrim(config('app.frontend_url', 'http://localhost:3000'), '/');
            return redirect()->away("{$frontendUrl}/login?error=social_auth_failed");
        }

        $user = User::firstOrCreate(
            ['provider' => $provider, 'provider_id' => $socialUser->getId()],
            [
                'name' => $socialUser->getName() ?? $socialUser->getEmail(),
                'email' => $socialUser->getEmail(),
                'avatar' => $socialUser->getAvatar(),
                'password' => Hash::make(str()->random(24)),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        if (!$user->provider) {
            $user->provider = $provider;
            $user->provider_id = $socialUser->getId();
            $user->avatar = $socialUser->getAvatar();
            $user->save();
        }

        $token = $user->createToken('api-token')->plainTextToken;

        $frontendUrl = rtrim(config('app.frontend_url', 'http://localhost:3000'), '/');
        $redirectUrl = "{$frontendUrl}/auth/callback?token={$token}&user=" . urlencode(json_encode([
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'phone' => $user->phone,
            'is_verified' => (bool) $user->email_verified_at,
            'created_at' => $user->created_at?->toISOString(),
        ]));

        return redirect()->away($redirectUrl);
    }

    public function link(Request $request): JsonResponse
    {
        $request->validate([
            'provider' => 'required|in:google',
            'access_token' => 'required|string',
        ]);

        $user = $request->user();
        $socialUser = Socialite::driver($request->provider)->stateless()->userFromToken($request->access_token);

        $existingUser = User::where('provider', $request->provider)
            ->where('provider_id', $socialUser->getId())
            ->where('id', '!=', $user->id)
            ->first();

        if ($existingUser) {
            throw ValidationException::withMessages(['provider' => ['This account is already linked to another user.']]);
        }

        $user->provider = $request->provider;
        $user->provider_id = $socialUser->getId();
        $user->avatar = $socialUser->getAvatar();
        $user->save();

        return response()->json(['message' => 'Account linked successfully.']);
    }

    public function unlink(Request $request): JsonResponse
    {
        $request->validate([
            'provider' => 'required|in:google',
        ]);

        $user = $request->user();

        if ($user->provider === $request->provider && !$user->password) {
            throw ValidationException::withMessages(['provider' => ['Cannot unlink your only login method. Set a password first.']]);
        }

        $user->provider = null;
        $user->provider_id = null;
        $user->avatar = null;
        $user->save();

        return response()->json(['message' => 'Account unlinked successfully.']);
    }
}