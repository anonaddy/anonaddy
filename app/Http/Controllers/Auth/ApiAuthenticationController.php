<?php

namespace App\Http\Controllers\Auth;

use App\Facades\Webauthn;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApiAuthenticationLoginRequest;
use App\Http\Requests\ApiAuthenticationMfaRequest;
use App\Models\User;
use App\Models\Username;
use Illuminate\Contracts\Encryption\DecryptException;
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
    }

    public function login(ApiAuthenticationLoginRequest $request)
    {
        $user = Username::firstWhere('username', $request->username)?->user;

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'error' => 'The provided credentials are incorrect',
            ], 401);
        }

        // Check if user has 2FA enabled, if needs OTP then return mfa_key
        if ($user->two_factor_enabled) {
            return response()->json([
                'message' => "OTP required, please make a request to /api/auth/mfa with the 'mfa_key', 'otp' and 'device_name' including a 'X-CSRF-TOKEN' header",
                'mfa_key' => Crypt::encryptString($user->id.'|'.config('anonaddy.secret').'|'.Carbon::now()->addMinutes(5)->getTimestamp()),
                'csrf_token' => csrf_token(),
            ], 422);
        } elseif (Webauthn::enabled($user)) {
            // If WebAuthn is enabled then return currently unsupported message
            return response()->json([
                'error' => 'WebAuthn authentication is not currently supported from the extension or mobile apps, please use an API key to login instead',
            ], 403);
        }

        // If the user doesn't use 2FA then return the new API key
        return response()->json([
            'api_key' => explode('|', $user->createToken($request->device_name)->plainTextToken, 2)[1],
        ]);
    }

    public function mfa(ApiAuthenticationMfaRequest $request)
    {
        try {
            $mfaKey = Crypt::decryptString($request->mfa_key);
        } catch (DecryptException $e) {
            return response()->json([
                'error' => 'Invalid mfa_key',
            ], 401);
        }
        $parts = explode('|', $mfaKey, 3);

        $user = User::find($parts[0]);

        if (! $user || $parts[1] !== config('anonaddy.secret')) {
            return response()->json([
                'error' => 'Invalid mfa_key',
            ], 401);
        }

        // Check if the mfa_key has expired
        if (Carbon::now()->getTimestamp() > $parts[2]) {
            return response()->json([
                'error' => 'mfa_key expired, please request a new one at /api/auth/login',
            ], 401);
        }

        $google2fa = new Google2FA();
        $lastTimeStamp = Cache::get('2fa_ts:'.$user->id);

        $timestamp = $google2fa->verifyKeyNewer($user->two_factor_secret, $request->otp, $lastTimeStamp);

        if (! $timestamp) {
            return response()->json([
                'error' => 'The \'One Time Password\' typed was wrong',
            ], 401);
        }

        if (is_int($timestamp)) {
            Cache::put('2fa_ts:'.$user->id, $timestamp, now()->addMinutes(5));
        }

        return response()->json([
            'api_key' => explode('|', $user->createToken($request->device_name)->plainTextToken, 2)[1],
        ]);
    }
}
