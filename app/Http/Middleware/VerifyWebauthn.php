<?php

namespace App\Http\Middleware;

use App\Facades\Webauthn;
use Closure;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Facades\Redirect;

class VerifyWebauthn
{
    /**
     * The config repository instance.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * The auth factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a Webauthn.
     *
     * @param \Illuminate\Contracts\Config\Repository $config
     * @param \Illuminate\Contracts\Auth\Factory $auth
     */
    public function __construct(Config $config, AuthFactory $auth)
    {
        $this->config = $config;
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if ((bool) $this->config->get('webauthn.enable', true) &&
            ! Webauthn::check()) {
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
