<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecipientResource;
use Illuminate\Http\Request;

class EncryptedRecipientController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['id' => 'required|string']);

        $recipient = user()->recipients()->findOrFail($request->id);

        if (! $recipient->fingerprint) {
            return response('You need to add a public key to this recipient before you can enable encryption', 422);
        }

        $recipient->update(['should_encrypt' => true]);

        return new RecipientResource($recipient->loadCount('aliases'));
    }

    public function destroy($id)
    {
        $recipient = user()->recipients()->findOrFail($id);

        $recipient->update(['should_encrypt' => false]);

        return response('', 204);
    }
}
