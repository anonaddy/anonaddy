<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Response;
use LaravelWebauthn\Facades\Webauthn;
use LaravelWebauthn\Http\Responses\LoginViewResponse as LoginViewResponseBase;

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
        if (! Webauthn::enabled($request->user())) {
            return Response::redirectTo('/');
        }

        $view = config('webauthn.views.authenticate', '');

        return $request->wantsJson()
            ? Response::json(['publicKey' => $this->publicKey])
            : Response::view($view, ['publicKey' => $this->publicKey]);
    }
}
