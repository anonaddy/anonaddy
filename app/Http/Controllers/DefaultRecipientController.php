<?php

namespace App\Http\Controllers;

use App\Http\Requests\EditDefaultRecipientRequest;
use App\Http\Requests\UpdateDefaultRecipientRequest;
use App\Notifications\DefaultRecipientUpdated;

class DefaultRecipientController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('throttle:1,1')->only('edit');
        $this->middleware('throttle:3,1')->only('update');
    }

    public function update(UpdateDefaultRecipientRequest $request)
    {
        $recipient = user()->verifiedRecipients()->findOrFail($request->id);

        $currentDefaultRecipient = user()->defaultRecipient;

        user()->update(['default_recipient_id' => $recipient->id]);

        if ($currentDefaultRecipient->id !== $recipient->id) {
            $currentDefaultRecipient->notify(new DefaultRecipientUpdated($recipient->email));
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function edit(EditDefaultRecipientRequest $request)
    {
        $recipient = user()->defaultRecipient;

        // Updating already verified default recipient, create new pending entry and send verification email.
        if ($recipient->hasVerifiedEmail()) {
            // Clear all other pending entries
            user()->pendingRecipients()->delete();

            $pendingRecipient = user()->recipients()->create([
                'email' => strtolower($request->email),
                'pending' => true,
            ]);

            $pendingRecipient->sendEmailVerificationNotification();

            return back()->with(['flash' => 'Email Pending Verification, Please Check Your Inbox For The Verification Email']);
        }

        // Unverified default recipient so we can simply update and send the verification email.
        $recipient->email = strtolower($request->email);
        $recipient->save();

        user()->sendEmailVerificationNotification();

        return back()->with(['flash' => 'Email Updated Successfully, Please Check Your Inbox For The Verification Email']);
    }
}
