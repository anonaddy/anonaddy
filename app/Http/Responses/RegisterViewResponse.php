<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Response;
use LaravelWebauthn\Http\Responses\RegisterViewResponse as RegisterViewResponseBase;

class RegisterViewResponse extends RegisterViewResponseBase
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        if ($request->user()?->two_factor_enabled) {
            return Response::redirectTo('/');
        }

        $view = config('webauthn.views.register', '');

        return $request->wantsJson()
            ? Response::json(['publicKey' => $this->publicKey])
            : Response::view($view, ['publicKey' => $this->publicKey]);
    }
}
