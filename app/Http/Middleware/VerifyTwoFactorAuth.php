<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Str;
use PragmaRX\Google2FALaravel\Middleware;
use PragmaRX\Google2FALaravel\Support\Authenticator;

class VerifyTwoFactorAuth extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $authenticator = app(Authenticator::class)->boot($request);

        if ($authenticator->isAuthenticated() || ! $request->user()->two_factor_enabled) {
            return $next($request);
        }

        if (! Str::endsWith($request->url(), '/login/2fa')) {
            $request->session()->put([
                'intended_path' => $request->fullUrl(),
            ]);
        }

        return $authenticator->makeRequestOneTimePasswordResponse();
    }
}
