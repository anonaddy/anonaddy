<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Recipient;
use App\Models\User;
use App\Notifications\DefaultRecipientUpdated;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\VerifiesEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

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
     * Show the email verification notice.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function show(Request $request)
    {
        return $request->user()->hasVerifiedEmail()
                        ? redirect($this->redirectPath())
                        : Inertia::render('Auth/Verify', ['flash' => $request->session()->get('resent', null) ? 'A fresh verification link has been sent to your email address.' : null]);
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
        $verifiable = User::find($request->route('id')) ?? Recipient::withPending()->find($request->route('id'));

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

        // Check if the verifiable is a pending new email Recipient
        if ($verifiable instanceof Recipient && $verifiable->pending) {

            try {
                DB::transaction(function () use ($verifiable) {
                    $user = $verifiable->user;
                    $defaultRecipient = $user->defaultRecipient;
                    // Notify the current default recipient of the change
                    // Have to use sendNow method here to ensure this notification is sent before the current defaultRecipient's email is updated below
                    Notification::sendNow($defaultRecipient, new DefaultRecipientUpdated($verifiable->email));

                    // Set verifiable email as new default recipient
                    $defaultRecipient->update([
                        'email' => strtolower($verifiable->email),
                        'email_verified_at' => now(),
                    ]);

                    // Delete pending verifiable
                    $verifiable->delete();
                });
            } catch (\Exception $e) {
                report($e);

                return redirect($redirect)
                    ->with(['flash' => 'An error has occurred, please try again later.']);
            }
        }

        return redirect($redirect)
            ->with('verified', true)
            ->with(['flash' => 'Email Address Verified Successfully']);
    }
}
