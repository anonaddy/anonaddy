<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Username;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

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
     * @return Factory|View
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
            'password' => [
                'required',
                'confirmed',
                Password::defaults(),
            ],
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
     * Get the response for a successful password reset.
     *
     * @param  string  $response
     * @return RedirectResponse|JsonResponse
     */
    protected function sendResetResponse(Request $request, $response)
    {
        Auth::logoutOtherDevices($request->password);

        // A reset may follow a compromised account, so API keys must stop working too
        $request->user()?->tokens()->delete();

        if ($request->wantsJson()) {
            return new JsonResponse(['message' => trans($response)], 200);
        }

        // The dashboard shows flash messages as a toast, it does not show status
        return redirect($this->redirectPath())
            ->with('status', trans($response))
            ->with('flash', trans($response));
    }

    /**
     * Get the response for a failed password reset.
     *
     * @param  string  $response
     * @return RedirectResponse|JsonResponse
     */
    protected function sendResetFailedResponse(Request $request, $response)
    {
        return back()
            ->withInput($request->only('username'))
            ->withErrors(['username' => trans($response)]);
    }
}
