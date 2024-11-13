<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Username;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;



class ProxyAuthentication
{
    private const proxyAuthenticationUsernameSessionKey = 'ProxyAuthenticationUsername';
    private bool $isProxyAuthenticationEnabled;
    private string $usernameHeaderName;
    private string $emailHeaderName;


    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->isProxyAuthenticationEnabled = config('anonaddy.use_proxy_authentication');
        $this->usernameHeaderName = config('anonaddy.proxy_authentication_username_header');
        $this->emailHeaderName = config('anonaddy.proxy_authentication_email_header');
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next) : Response
    {
        if ($this->isProxyAuthenticationEnabled)
        {
            return $this->handleProxyAuthentication($request, $next);
        }

        return $next($request);
    }

    private function handleProxyAuthentication(Request $request, Closure $next) : Response
    {
        $loggedInUsername = $request->session()->get(self::proxyAuthenticationUsernameSessionKey);
        $username = $request->header($this->usernameHeaderName);
        $email = $request->header($this->emailHeaderName);
        
        $loggedOut = $this->logoutWhenNeeded($request, $loggedInUsername, $username);
        $loggedIn = $this->loginWhenNeeded($request, $username, $email);
        
        if ($loggedIn)
        {
            return getLoginRedirectResponse();
        }
        if ($loggedOut)
        {
            return redirect('/login');
        }

        return $next($request);
    }

    private function logoutWhenNeeded(Request $request, string|null $loggedInUsername, string|null $username) : bool
    {
        if (Auth::check())
        {
            $loggedInElsewhereButCurrentlyHasHeaders = $this->isNullOrEmptyString($loggedInUsername) && $this->hasProxyAuthenticationHeaders($request);
            $loggedInFromProxyButCurrentlyNoHeaders = !$this->isNullOrEmptyString($loggedInUsername) && !$this->hasProxyAuthenticationHeaders($request);
            $loggedinFromProxyButUsernameDoesNotMatch = !$this->isNullOrEmptyString($loggedInUsername) && $loggedInUsername !== $username;

            if ($loggedInElsewhereButCurrentlyHasHeaders ||
                $loggedInFromProxyButCurrentlyNoHeaders ||
                $loggedinFromProxyButUsernameDoesNotMatch)
            {
                Auth::logout();
                $request->session()->flush();
                return true;
            }
        }

        return false;
    }

    private function loginWhenNeeded(Request $request, string|null $username, string|null $email) : bool
    {
        $notloggedInButHeadersProvided = !Auth::check() && !$this->isNullOrEmptyString($username) && !$this->isNullOrEmptyString($email);
        if ($notloggedInButHeadersProvided)
        {
            $userId = $this->getValidUserIdForUsername($username);
            if ($userId === null)
            {
                $userId = createUser($username, $email, emailVerified: true)->id;
            }

            Auth::loginUsingId($userId);
            $request->session()->put(self::proxyAuthenticationUsernameSessionKey, $username);
            $request->session()->regenerate();
            return true;
        }

        return false;
    }

    private function getValidUserIdForUsername(string $username) : string|null
    {
        $usernameModel = Username::select(['user_id', 'username', 'can_login', 'active'])
            ->where('username', $username)
            ->first();

        if ($usernameModel !== null && ($usernameModel->can_login === false || $usernameModel->active === false))
        {
            abort(401);
        } 

        return $usernameModel?->user_id;
    }

    private function hasProxyAuthenticationHeaders(Request $request) : bool 
    {
        return $request->hasHeader($this->usernameHeaderName) && $request->hasHeader($this->emailHeaderName);
    }

    private function isNullOrEmptyString(string|null $str) : bool
    {
        return $str === null || trim($str) === '';
    }
}
