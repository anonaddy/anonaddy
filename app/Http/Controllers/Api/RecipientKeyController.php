<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRecipientKeyRequest;
use App\Http\Resources\RecipientResource;

class RecipientKeyController extends Controller
{
    protected $gnupg;

    public function __construct()
    {
        $this->gnupg = new \gnupg();
    }

    public function update(UpdateRecipientKeyRequest $request, $id)
    {
        $recipient = user()->recipients()->findOrFail($id);

        $info = $this->gnupg->import($request->key_data);

        if (! $info || ! $info['fingerprint']) {
            return response('Key could not be imported', 404);
        }

        $recipient->update([
            'should_encrypt' => true,
            'fingerprint' => $info['fingerprint'],
        ]);

        return new RecipientResource($recipient->fresh()->load('aliases'));
    }

    public function destroy($id)
    {
        $recipient = user()->recipients()->findOrFail($id);

        user()->deleteKeyFromKeyring($recipient->fingerprint);

        $recipient->update([
            'should_encrypt' => false,
            'inline_encryption' => false,
            'protected_headers' => false,
            'inline_encryption' => false,
            'protected_headers' => false,
            'fingerprint' => null,
        ]);

        return response('', 204);
    }
}
