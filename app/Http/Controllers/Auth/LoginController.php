<?php

namespace App\Http\Controllers\Auth;

use App\Enums\LoginRedirect;
use App\Http\Controllers\Controller;
use App\Models\Username;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function redirectTo()
    {
        // Dynamic redirect setting to allow users to choose to go to /aliases page instead etc.
        return match (user()->login_redirect) {
            LoginRedirect::ALIASES => '/aliases',
            LoginRedirect::RECIPIENTS => '/recipients',
            LoginRedirect::USERNAMES => '/usernames',
            LoginRedirect::DOMAINS => '/domains',
            default => '/',
        };
    }

    public function username()
    {
        return 'id';
    }

    public function addIdToRequest()
    {
        $userId = Username::select(['user_id', 'username', 'can_login'])
            ->where('username', request()->input('username'))
            ->where('can_login', true)
            ->first()?->user_id;

        request()->merge(['id' => $userId]);
    }

    /**
     * Validate the user login request.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateLogin(Request $request)
    {
        $this->addIdToRequest();

        Validator::make($request->all(), [
            'username' => 'required|regex:/^[a-zA-Z0-9]*$/|min:1|max:20',
            'password' => 'required|string',
            $this->username() => 'nullable|string',
        ], [
            'username.regex' => 'Your username can only contain letters and numbers, do not use your email.',
        ])->validate();
    }

    /**
     * Get the needed authorization credentials from the request.
     *
     * @return array
     */
    protected function credentials(Request $request)
    {
        return $request->only('id', 'password');
    }

    /**
     * Send the response after the user was authenticated.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function sendLoginResponse(Request $request)
    {
        $request->session()->regenerate();

        $this->clearLoginAttempts($request);

        if ($response = $this->authenticated($request, $this->guard()->user())) {
            return $response;
        }

        // If the intended path is just the dashboard then ignore and use the user's login redirect instead
        $redirectTo = $this->redirectTo();
        $intended = session()->pull('url.intended');

        return $intended === url('/') ? redirect()->to($redirectTo) : redirect()->intended($intended ?? $redirectTo);
    }

    /**
     * Get the failed login response instance.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        throw ValidationException::withMessages([
            'username' => [trans('auth.failed')],
        ]);
    }

    /**
     * The user has logged out of the application.
     *
     * @return mixed
     */
    protected function loggedOut(Request $request)
    {
        return Inertia::location(route('login'));
    }
}
