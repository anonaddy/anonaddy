<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use App\Models\Username;
use App\Rules\NotBlacklisted;
use App\Rules\NotDeletedUsername;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;



class ProxyAuthentication
{
    public const proxyAuthenticationUsernameSessionKey = 'ProxyAuthenticationUsername';
    private bool $isProxyAuthenticationEnabled;
    private string $externalUserIdHeaderName;
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
        $this->externalUserIdHeaderName = config('anonaddy.proxy_authentication_external_user_id_header');
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
        $loggedInExternalUserId = $request->session()->get(self::proxyAuthenticationUsernameSessionKey);
        $externalUserId = $request->header($this->externalUserIdHeaderName);
        $username = substr($request->header($this->usernameHeaderName), start: 0, length: 20);
        $email = strtolower($request->header($this->emailHeaderName));
        
        $loggedOut = $this->logoutWhenNeeded($request, $loggedInExternalUserId, $externalUserId);
        $loggedIn = $this->loginWhenNeeded($request, $externalUserId, $username, $email);
        
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

    private function logoutWhenNeeded(Request $request, string|null $loggedInExternalUserId, string|null $externalUserId) : bool
    {
        if (Auth::check())
        {
            $loggedInElsewhereButCurrentlyHasHeaders = $this->isNullOrEmptyString($loggedInExternalUserId) && $this->hasProxyAuthenticationHeaders($request);
            $loggedInFromProxyButCurrentlyNoHeaders = !$this->isNullOrEmptyString($loggedInExternalUserId) && !$this->hasProxyAuthenticationHeaders($request);
            $loggedinFromProxyButUsernameDoesNotMatch = !$this->isNullOrEmptyString($loggedInExternalUserId) && $loggedInExternalUserId !== $externalUserId;

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

    private function loginWhenNeeded(Request $request, string|null $externalUserId, string|null $username, string|null $email) : bool
    {
        $notloggedInButHeadersProvided = !Auth::check() && !$this->isNullOrEmptyString($externalUserId) && !$this->isNullOrEmptyString($username) && !$this->isNullOrEmptyString($email);
        if ($notloggedInButHeadersProvided)
        {
            $userId = $this->getValidUserIdForExternalId($externalUserId);
            if ($userId === null)
            {
                $userId = $this->createUser($username, $email, $externalUserId)->id;
            }

            Auth::loginUsingId($userId);
            $request->session()->put(self::proxyAuthenticationUsernameSessionKey, $externalUserId);
            $request->session()->regenerate();

            $this->updateDefaultRecipientIfNeeded($email);
            
            return true;
        }

        return false;
    }

    private function createUser($username, $email, $externalId) : User
    {
        $input = ['username' => $username ];

        $baseUsernamevalidator = Validator::make($input, ['username' => [
                'bail',
                'regex:/^[a-zA-Z0-9]*$/',
                new NotBlacklisted
            ]]);

        if ($baseUsernamevalidator->fails()) 
        {
            abort(403);
        }

        $generatedUsername = $this->generateUniqueUsername($username);

        if ($this->isNullOrEmptyString($generatedUsername))
        {
            abort(403);
        }

        return createUser($generatedUsername, $email, emailVerified: true, externalId: $externalId);
    }

    private function generateUniqueUsername(string $username) : string|null
    {
        $generatedUsername = $username;

        for ($try = 1; $try < 10; $try++)
        {
            $input = ['username' => $generatedUsername ];
            $uniqueValidator = Validator::make($input, ['username' => ['unique:usernames,username', new NotDeletedUsername]]);

            if (!$uniqueValidator->fails())
            {
                return $generatedUsername;
            }

            $generatedUsername = substr($username, start: 0, length: 19) . (string)$try;
        }

        return null;
    }

    private function getValidUserIdForExternalId(string $externalId) : string|null
    {
        $usernameModel = Username::select(['user_id', 'username', 'can_login'])
            ->where('external_id', $externalId)
            ->first();

        if ($usernameModel !== null && $usernameModel->can_login === false)
        {
            abort(401);
        } 

        return $usernameModel?->user_id;
    }

    private function hasProxyAuthenticationHeaders(Request $request) : bool 
    {
        return $request->hasHeader($this->usernameHeaderName) 
            && $request->hasHeader($this->emailHeaderName)
            && $request->hasHeader($this->externalUserIdHeaderName);
    }

    private function isNullOrEmptyString(string|null $str) : bool
    {
        return $str === null || trim($str) === '';
    }

    private function updateDefaultRecipientIfNeeded(string $email) : void 
    {
        $recipient = Auth::user()->defaultRecipient;

        if ($recipient->email === $email)
        {
            return;
        }

        $recipient->email = $email;
        $recipient->save();
    }
}
