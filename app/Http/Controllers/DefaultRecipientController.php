<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateDefaultRecipientRequest;

class DefaultRecipientController extends Controller
{
    public function update(UpdateDefaultRecipientRequest $request)
    {
        $recipient = user()->verifiedRecipients()->findOrFail($request->default_recipient);

        user()->default_recipient = $recipient;
        user()->save();

        return back()->with(['status' => 'Default Recipient Updated Successfully']);
    }
}
