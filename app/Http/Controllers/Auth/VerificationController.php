<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Recipient;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\VerifiesEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class VerificationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Email Verification Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling email verification for any
    | user that recently registered with the application. Emails may also
    | be re-sent if the user didn't receive the original email message.
    |
    */

    use VerifiesEmails;

    /**
     * Where to redirect users after verification.
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
        $this->middleware('auth')->except('verify');
        $this->middleware('signed')->only('verify');
        $this->middleware('throttle:1,1')->only('resend');
        $this->middleware('throttle:6,1')->only('verify');
    }

    /**
     * Mark the authenticated user's email address as verified.
     *
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function verify(Request $request)
    {
        $verifiable = User::find($request->route('id')) ?? Recipient::find($request->route('id'));

        if (is_null($verifiable)) {
            throw new AuthorizationException('Email address not found.');
        }

        if (! hash_equals((string) $request->route('id'), (string) $verifiable->getKey())) {
            throw new AuthorizationException('Invalid hash.');
        }

        if (! Hash::check($verifiable->getEmailForVerification(), (string) base64_decode($request->route('hash')))) {
            throw new AuthorizationException('Invalid hash.');
        }

        if ($verifiable->hasVerifiedEmail()) {
            return redirect($this->redirectPath());
        }

        if ($verifiable->markEmailAsVerified() && $verifiable instanceof User) {
            event(new Verified($verifiable));
        }

        if ($request->user() !== null) {
            $redirect = $verifiable instanceof User ? $this->redirectPath() : route('recipients.index');
        } else {
            $redirect = 'login';
        }

        return redirect($redirect)
            ->with('verified', true)
            ->with(['status' => 'Email Address Verified Successfully']);
    }
}
