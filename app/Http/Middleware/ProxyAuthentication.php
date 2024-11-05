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
        if ($this->isProxyAuthenticationEnabled && $this->hasProxyAuthenticationHeaders($request))
        {
            return $this->handleProxyAuthentication($request, $next);
        }
        else
        {
            return parent::handle($request, $next);
        }
    }

    private function handleProxyAuthentication(Request $request, Closure $next)
    {
        if (!Auth::check())
        {
            $username = $request->header($this->usernameHeaderName);
            $email = $request->header($this->emailHeaderName);

            if ($this->isNullOrEmptyString($username) || $this->isNullOrEmptyString($email))
            {
                abort(400);
            }

            $userId = $this->getValidUserIdForUsername($username);

            if ($userId === null)
            {
                $userId = createUser($username, $email)->id;
            }

            Auth::loginUsingId($userId);
            $request->session()->regenerate();
        }

        return $next($request);
    }

    private function getValidUserIdForUsername(string $username) : string|null
    {
        $usernameModel = Username::select(['user_id', 'username', 'can_login'])
            ->where('username', $username)
            ->first();

        if ($usernameModel !== null && $usernameModel->can_login === false)
        {
            abort(401);
        } 

        return $usernameModel?->user_id;
    }

    private function hasProxyAuthenticationHeaders(Request $request): bool 
    {
        return $request->hasHeader($this->usernameHeaderName) && $request->hasHeader($this->emailHeaderName);
    }

    private function isNullOrEmptyString(string|null $str)
    {
        return $str === null || trim($str) === '';
    }
}
