<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUsernameDefaultRecipientRequest;
use App\Http\Resources\UsernameResource;

class UsernameDefaultRecipientController extends Controller
{
    public function update(UpdateUsernameDefaultRecipientRequest $request, $id)
    {
        $username = user()->usernames()->findOrFail($id);
        if (empty($request->default_recipient)) {
            $username->default_recipient_id = null;
        } else {
            $recipient = user()->verifiedRecipients()->findOrFail($request->default_recipient);
            $username->default_recipient = $recipient;
        }

        $username->save();

        return new UsernameResource($username->load(['aliases', 'defaultRecipient']));
    }
}
