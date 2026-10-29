<?php

namespace App\Http\Middleware;

use App\Facades\Webauthn;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Sanctum\TransientToken;
use PragmaRX\Google2FALaravel\Support\Authenticator;
use Symfony\Component\HttpFoundation\Response;

class VerifyTwoFactorAuthMethods
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = $request->user();

        // If user has no 2FA methods enabled or uses external authentication, continue
        if (! $user->hasAnyTwoFactorEnabled() || usesExternalAuthentication()) {
            return $next($request);
        }

        // API keys are only issued after 2FA has been passed, a session (TransientToken) has not
        if ($this->isAuthenticatedWithApiKey($request)) {
            return $next($request);
        }

        // Only accept a one-time password on the throttled TOTP login route, otherwise any route could be used to brute force it
        if (! $request->routeIs('login.2fa')) {
            $otpInput = config('google2fa.otp_input');

            $request->query->remove($otpInput);
            $request->offsetUnset($otpInput);
        }

        // Check if user is already authenticated with any 2FA method
        $totpAuthenticator = app(Authenticator::class)->boot($request);

        $totpAuthenticated = $user->hasTotpEnabled() && $totpAuthenticator->isAuthenticated();
        $webauthnAuthenticated = $user->hasWebauthnEnabled() && Webauthn::check();

        if ($totpAuthenticated || $webauthnAuthenticated) {
            return $next($request);
        }

        // Session authenticated API requests (statefulApi) must not skip 2FA
        if ($request->is('api/*')) {
            return response()->json([
                'message' => 'Two-factor authentication required.',
            ], 403);
        }

        // If user has both methods, redirect to default webauthn
        if ($user->hasTotpEnabled() && $user->hasWebauthnEnabled()) {
            // Allow access to TOTP and WebAuthn login pages and backup code
            // Check the GET and POST routes to allow incorrect TOTPs
            if ($request->routeIs('login.2fa.index') || $request->routeIs('login.2fa')) {
                return $this->totpChallenge($request, $totpAuthenticator);
            }

            // Kept for the "use authenticator app instead" link on the security-key page
            $request->session()->put([
                'intended_path' => $request->fullUrl(),
            ]);

            return $this->webauthnChallenge($request);
        }

        // If user only has TOTP, use existing TOTP flow
        if ($user->hasTotpEnabled() && ! $user->hasWebauthnEnabled()) {
            return $this->totpChallenge($request, $totpAuthenticator);
        }

        // If user only has WebAuthn, use existing WebAuthn flow
        if ($user->hasWebauthnEnabled() && ! $user->hasTotpEnabled()) {
            return $this->webauthnChallenge($request);
        }

        return $next($request);
    }

    /**
     * Send the browser to the authenticator-app challenge.
     *
     * An Inertia visit cannot render that Blade page. A full-page visit loads it as a document, so the form can be submitted.
     */
    protected function totpChallenge(Request $request, Authenticator $totpAuthenticator): Response
    {
        if ($request->isMethod('post') && $request->routeIs('login.2fa')) {
            return $totpAuthenticator->makeRequestOneTimePasswordResponse();
        }

        if (! $request->routeIs('login.2fa.index')) {
            $request->session()->put([
                'intended_path' => $request->fullUrl(),
            ]);
        }

        if ($request->inertia()) {
            return Inertia::location(route('login.2fa.index'));
        }

        return $totpAuthenticator->makeRequestOneTimePasswordResponse();
    }

    /**
     * Send the browser to the security-key challenge.
     *
     * An Inertia visit cannot run the WebAuthn script. A full-page visit loads it as a document.
     */
    protected function webauthnChallenge(Request $request): Response
    {
        if ($request->inertia()) {
            if ($request->hasSession() && ! $request->session()->has('url.intended')) {
                $returnUrl = $request->isMethod('GET') ? $request->fullUrl() : url()->previous();

                $request->session()->put('url.intended', $returnUrl);
            }

            return Inertia::location(route('webauthn.login'));
        }

        if ($request->hasSession() && $request->session()->has('url.intended')) {
            return redirect()->to(route('webauthn.login'));
        }

        return redirect()->guest(route('webauthn.login'));
    }

    /**
     * Determine if the request was authenticated with an API key rather than the session.
     */
    protected function isAuthenticatedWithApiKey(Request $request): bool
    {
        $token = $request->user()->currentAccessToken();

        return $token !== null && ! $token instanceof TransientToken;
    }
}
