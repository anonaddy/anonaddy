<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\VerifiesEmails;
use Illuminate\Http\Request;

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
        $this->middleware('auth');
        $this->middleware('signed')->only('verify');
        $this->middleware('throttle:1,1')->only('resend');
        $this->middleware('throttle:60,1')->only('verify');
    }

    /**
     * Mark the authenticated user's email address as verified.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function verify(Request $request)
    {
        if ($recipient = $request->user()->recipients()->find($request->route('id'))) {
            if ($recipient->hasVerifiedEmail()) {
                return redirect($this->redirectPath());
            }

            $recipient->markEmailAsVerified();

            return redirect(route('recipients.index'))
                ->with('verified', true)
                ->with(['status' => 'Recipient Email Address Verified Successfully']);
        } else {
            if ($request->route('id') != $request->user()->getKey()) {
                throw new AuthorizationException;
            }

            if ($request->user()->hasVerifiedEmail()) {
                return redirect($this->redirectPath());
            }

            if ($request->user()->markEmailAsVerified()) {
                event(new Verified($request->user()));
            }

            return redirect($this->redirectPath())->with('verified', true);
        }
    }
}
