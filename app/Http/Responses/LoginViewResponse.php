<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Response;
use LaravelWebauthn\Facades\Webauthn;
use LaravelWebauthn\Http\Responses\LoginViewResponse as LoginViewResponseBase;
use PragmaRX\Google2FALaravel\Support\Authenticator;

class LoginViewResponse extends LoginViewResponseBase
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        // If user has no 2FA methods enabled, redirect home
        if (! $request->user()->hasAnyTwoFactorEnabled()) {
            return Response::redirectTo('/');
        }

        // Check if user is already authenticated with any 2FA method, if so then redirect home
        $totpAuthenticator = app(Authenticator::class)->boot($request);

        if ($totpAuthenticator->isAuthenticated() || Webauthn::check()) {
            return Response::redirectTo('/');
        }

        $view = config('webauthn.views.authenticate', '');

        return $request->wantsJson()
            ? Response::json(['publicKey' => $this->publicKey])
            : Response::view($view, ['publicKey' => $this->publicKey]);
    }
}
