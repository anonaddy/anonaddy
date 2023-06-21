<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Username;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
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
        $this->middleware('guest');
    }

    /**
     * Display the password reset view for the given token.
     *
     * If no token is present, display the link request form.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showResetForm(Request $request)
    {
        $token = $request->route()->parameter('token');

        return view('auth.passwords.reset')->with(
            ['token' => $token, 'username' => $request->username]
        );
    }

    /**
     * Get the password reset validation rules.
     *
     * @return array
     */
    protected function rules()
    {
        return [
            'token' => 'required',
            'username' => 'required|regex:/^[a-zA-Z0-9]*$/|max:20',
            'password' => 'required|confirmed|min:8',
        ];
    }

    /**
     * Get the password reset credentials from the request.
     *
     * @return array
     */
    protected function credentials(Request $request)
    {
        // Find the user_id and use that for the credentials
        $userId = Username::firstWhere('username', $request->username)?->user_id;

        $request->merge(['id' => $userId]);

        return $request->only(
            'id',
            'password',
            'password_confirmation',
            'token'
        );
    }

    /**
     * Get the response for a failed password reset.
     *
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function sendResetFailedResponse(Request $request, $response)
    {
        return back()
            ->withInput($request->only('username'))
            ->withErrors(['username' => trans($response)]);
    }
}
