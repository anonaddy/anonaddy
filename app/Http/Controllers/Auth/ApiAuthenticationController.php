<?php

namespace App\Http\Controllers\Auth;

use App\Facades\Webauthn;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApiAuthenticationLoginRequest;
use App\Http\Requests\ApiAuthenticationMfaRequest;
use App\Http\Requests\DestroyAccountRequest;
use App\Jobs\DeleteAccount;
use App\Models\User;
use App\Models\Username;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

class ApiAuthenticationController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:3,1');

        $this->middleware(['auth:sanctum', 'verified'])->only(['logout', 'destroy']);
    }

    public function login(ApiAuthenticationLoginRequest $request)
    {
        $user = Username::select(['user_id', 'username'])->firstWhere('username', $request->username)?->user;

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }

        if (! $user->hasVerifiedDefaultRecipient()) {
            // Send email verification email
            $user->sendApiRegistrationEmailVerificationNotification($request->device_name, $request->expiration);

            return response()->json([
                'message' => 'Your email address is not verified. A fresh verification email has just been sent to you.',
            ], 401);
        }

        // Check if user has 2FA enabled, if needs OTP then return mfa_key
        if ($user->two_factor_enabled) {
            return response()->json([
                'message' => "OTP required, please make a request to /api/auth/mfa with the 'mfa_key', 'otp' and 'device_name' including a 'X-CSRF-TOKEN' header.",
                'mfa_key' => Crypt::encryptString($user->id.'|'.config('anonaddy.secret').'|'.Carbon::now()->addMinutes(5)->getTimestamp()),
                'csrf_token' => csrf_token(),
            ], 422);
        } elseif (Webauthn::enabled($user)) {
            // If WebAuthn is enabled then return currently unsupported message
            return response()->json([
                'message' => 'Security key authentication is not currently supported from the extension or mobile apps, please use an API key to login instead.',
            ], 403);
        }

        // day, week, month, year or null
        if ($request->expiration) {
            $method = 'add'.ucfirst($request->expiration);
            $expiration = now()->{$method}();
        } else {
            $expiration = null;
        }

        // Token expires after 3 months, user must re-login
        $newToken = $user->createToken($request->device_name, ['*'], $expiration);
        $token = $newToken->accessToken;

        // If the user doesn't use 2FA then return the new API key
        return response()->json([
            'api_key' => explode('|', $newToken->plainTextToken, 2)[1],
            'name' => $token->name,
            'created_at' => $token->created_at?->toDateTimeString(),
            'expires_at' => $token->expires_at?->toDateTimeString(),
        ]);
    }

    public function mfa(ApiAuthenticationMfaRequest $request)
    {
        try {
            $mfaKey = Crypt::decryptString($request->mfa_key);
        } catch (DecryptException $e) {
            return response()->json([
                'message' => 'Invalid mfa_key.',
            ], 401);
        }
        $parts = explode('|', $mfaKey, 3);

        $user = User::find($parts[0]);

        if (! $user || $parts[1] !== config('anonaddy.secret')) {

            return response()->json([
                'message' => 'Invalid mfa_key.',
            ], 401);
        }

        // Check if the mfa_key has expired
        if (Carbon::now()->getTimestamp() > $parts[2]) {

            return response()->json([
                'message' => 'mfa_key expired, please request a new one at /api/auth/login.',
            ], 401);
        }

        $google2fa = new Google2FA;
        $lastTimeStamp = Cache::get('2fa_ts:'.$user->id, 0);

        $timestamp = $google2fa->verifyKeyNewer($user->two_factor_secret, $request->otp, $lastTimeStamp, config('google2fa.window'));

        if (! $timestamp) {

            return response()->json([
                'message' => 'The \'One Time Password\' typed was wrong.',
            ], 401);
        }

        if (is_int($timestamp)) {
            Cache::put('2fa_ts:'.$user->id, $timestamp, now()->addMinutes(5));
        }

        // day, week, month, year or null
        if ($request->expiration) {
            $method = 'add'.ucfirst($request->expiration);
            $expiration = now()->{$method}();
        } else {
            $expiration = null;
        }

        $newToken = $user->createToken($request->device_name, ['*'], $expiration);
        $token = $newToken->accessToken;

        return response()->json([
            'api_key' => explode('|', $newToken->plainTextToken, 2)[1],
            'name' => $token->name,
            'created_at' => $token->created_at?->toDateTimeString(),
            'expires_at' => $token->expires_at?->toDateTimeString(),
        ]);
    }

    public function logout(Request $request)
    {
        $token = $request->user()?->currentAccessToken();

        if (! $token) {
            return response()->json([
                'message', 'API key not found.',
            ], 404);
        }

        $token->delete();

        return response()->json([], 204);
    }

    public function destroy(DestroyAccountRequest $request)
    {
        DeleteAccount::dispatch($request->user());

        return response()->json([], 204);
    }
}
