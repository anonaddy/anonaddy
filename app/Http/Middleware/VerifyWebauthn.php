<?php

namespace App\Http\Middleware;

use App\Facades\Webauthn;
use Closure;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Support\Facades\Redirect;

class VerifyWebauthn
{
    /**
     * The auth factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a Webauthn.
     *
     * @param  \Illuminate\Contracts\Config\Repository  $config
     */
    public function __construct(AuthFactory $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (Webauthn::webauthnEnabled() && ! Webauthn::check()) {
            abort_if($this->auth->guard($guard)->guest(), 401, trans('webauthn::errors.user_unauthenticated'));

            if (Webauthn::enabled($request->user($guard))) {
                if ($request->hasSession() && $request->session()->has('url.intended')) {
                    return Redirect::to(route('webauthn.login'));
                } else {
                    return Redirect::guest(route('webauthn.login'));
                }
            }
        }

        return $next($request);
    }
}
