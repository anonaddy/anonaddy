<?php

namespace App\Http\Middleware;

use App\Facades\Webauthn;
use Closure;
use PragmaRX\Google2FALaravel\Support\Authenticator;

class VerifyTwoFactorAuthMethods
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = $request->user();

        // If user has no 2FA methods enabled or uses external authentication, continue
        if (! $user->hasAnyTwoFactorEnabled() || usesExternalAuthentication()) {
            return $next($request);
        }

        // Check if user is already authenticated with any 2FA method
        $totpAuthenticator = app(Authenticator::class)->boot($request);

        if ($totpAuthenticator->isAuthenticated() || Webauthn::check()) {
            return $next($request);
        }

        // If user has both methods, redirect to default webauthn
        if ($user->hasTotpEnabled() && $user->hasWebauthnEnabled()) {
            // Allow access to TOTP and WebAuthn login pages and backup code
            // Check the GET and POST routes to allow incorrect TOTPs
            if ($request->routeIs('login.2fa.index') || $request->routeIs('login.2fa')) {

                return $totpAuthenticator->makeRequestOneTimePasswordResponse();
            }

            // If user is trying to access any other protected route, redirect to default webauthn
            $request->session()->put([
                'intended_path' => $request->fullUrl(),
            ]);

            // By default redirect to webauthn 2FA page
            if ($request->hasSession() && $request->session()->has('url.intended')) {
                return redirect()->to(route('webauthn.login'));
            } else {
                return redirect()->guest(route('webauthn.login'));
            }
        }

        // If user only has TOTP, use existing TOTP flow
        if ($user->hasTotpEnabled() && ! $user->hasWebauthnEnabled()) {
            if (! $request->routeIs('login.2fa.index')) {
                $request->session()->put([
                    'intended_path' => $request->fullUrl(),
                ]);
            }

            return $totpAuthenticator->makeRequestOneTimePasswordResponse();
        }

        // If user only has WebAuthn, use existing WebAuthn flow
        if ($user->hasWebauthnEnabled() && ! $user->hasTotpEnabled()) {
            if ($request->hasSession() && $request->session()->has('url.intended')) {
                return redirect()->to(route('webauthn.login'));
            } else {
                return redirect()->guest(route('webauthn.login'));
            }
        }

        return $next($request);
    }
}
