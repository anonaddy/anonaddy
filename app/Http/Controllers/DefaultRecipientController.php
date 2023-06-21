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
        $recipient = user()->verifiedRecipients()->findOrFail($request->default_recipient);

        $currentDefaultRecipient = user()->defaultRecipient;

        user()->default_recipient = $recipient;
        user()->save();

        if ($currentDefaultRecipient->id !== $recipient->id) {
            $currentDefaultRecipient->notify(new DefaultRecipientUpdated($recipient->email));
        }

        return back()->with(['status' => 'Default Recipient Updated Successfully']);
    }

    public function edit(EditDefaultRecipientRequest $request)
    {
        $recipient = user()->defaultRecipient;

        $recipient->email = $request->email;
        $recipient->save();

        user()->sendEmailVerificationNotification();

        return back()->with(['status' => 'Email Updated Successfully, Please Check Your Inbox For The Verification Email']);
    }
}
