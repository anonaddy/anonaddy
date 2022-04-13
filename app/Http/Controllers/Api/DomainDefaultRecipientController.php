<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateDomainDefaultRecipientRequest;
use App\Http\Resources\DomainResource;

class DomainDefaultRecipientController extends Controller
{
    public function update(UpdateDomainDefaultRecipientRequest $request, $id)
    {
        $domain = user()->domains()->findOrFail($id);
        if (empty($request->default_recipient)) {
            $domain->default_recipient_id = null;
        } else {
            $recipient = user()->verifiedRecipients()->findOrFail($request->default_recipient);
            $domain->default_recipient = $recipient;
        }

        $domain->save();

        return new DomainResource($domain->load(['aliases', 'defaultRecipient']));
    }
}
