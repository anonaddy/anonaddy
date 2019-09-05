<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateDefaultRecipientRequest;

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
    }

    public function update(UpdateDefaultRecipientRequest $request)
    {
        $recipient = user()->verifiedRecipients()->findOrFail($request->default_recipient);

        user()->default_recipient = $recipient;
        user()->save();

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
