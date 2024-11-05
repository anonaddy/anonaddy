<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Username;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;


class ProxyAuthentication extends AuthenticateSession 
{
    private bool $isProxyAuthenticationEnabled;
    private string $usernameHeaderName;
    private string $emailHeaderName;


    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(AuthFactory  $auth)
    {
        $this->isProxyAuthenticationEnabled = config('anonaddy.use_proxy_authentication');
        $this->usernameHeaderName = config('anonaddy.proxy_authentication_username_header');
        $this->emailHeaderName = config('anonaddy.proxy_authentication_email_header');
        parent::__construct($auth);
    }


    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        if (!Auth::check() && $request != null && $this->isProxyAuthenticationEnabled)
        {
            $this->handleProxyAuthentication($request);
            return tap($next($request), function () use ($request) {
                $this->handleProxyAuthentication($request);
            });
        }
        else
        {
            return parent::handle($request, $next);
        }
    }

    private function handleProxyAuthentication(Request $request)
    {
        $username = $request->header($this->usernameHeaderName);
        $email = $request->header($this->emailHeaderName);

        if ($this->isNullOrEmptyString($username) || $this->isNullOrEmptyString($email))
        {
            abort(401);
        }

        $usernameModel = Username::select(['user_id', 'username', 'can_login'])
            ->where('username', $username)
            ->first();

        if ($usernameModel !== null && $usernameModel->can_login === false)
        {
            abort(401);
        }

        if ($usernameModel === null)
        {
            $userId = createUser($username, $email)->id;
        }
        else
        {
            $userId = $usernameModel->user_id;
        }

        Auth::loginUsingId($userId);
        $request->session()->regenerate();
    }

    private function isNullOrEmptyString(string|null $str){
        return $str === null || trim($str) === '';
    }
}
