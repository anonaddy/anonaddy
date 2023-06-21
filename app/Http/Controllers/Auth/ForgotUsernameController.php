<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Recipient;
use Illuminate\Http\Request;

class ForgotUsernameController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
        $this->middleware('throttle:3,1')->only('sendReminderEmail');
    }

    /**
     * Display the form to request a password reset link.
     *
     * @return \Illuminate\View\View
     */
    public function show()
    {
        return view('auth.usernames.email');
    }

    /**
     * Send a reset link to the given user.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function sendReminderEmail(Request $request)
    {
        $this->validateEmail($request);

        $recipient = Recipient::select(['id', 'user_id', 'email', 'should_encrypt', 'fingerprint', 'email_verified_at'])->whereNotNull('email_verified_at')->get()->firstWhere('email', strtolower($request->email));

        if (isset($recipient)) {
            $recipient->sendUsernameReminderNotification();
        }

        return back()->with('status', 'A reminder has been sent if that email exists.');
    }

    /**
     * Validate the email for the given request.
     *
     * @return void
     */
    protected function validateEmail(Request $request)
    {
        $request->validate(['email' => 'required|email:rfc']);
    }
}
