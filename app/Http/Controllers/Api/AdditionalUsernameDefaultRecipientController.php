<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAdditionalUsernameDefaultRecipientRequest;
use App\Http\Resources\AdditionalUsernameResource;

class AdditionalUsernameDefaultRecipientController extends Controller
{
    public function update(UpdateAdditionalUsernameDefaultRecipientRequest $request, $id)
    {
        $additionalUsername = user()->additionalUsernames()->findOrFail($id);
        if (empty($request->default_recipient)) {
            $additionalUsername->default_recipient_id = null;
        } else {
            $recipient = user()->verifiedRecipients()->findOrFail($request->default_recipient);
            $additionalUsername->default_recipient = $recipient;
        }

        $additionalUsername->save();

        return new AdditionalUsernameResource($additionalUsername);
    }
}
