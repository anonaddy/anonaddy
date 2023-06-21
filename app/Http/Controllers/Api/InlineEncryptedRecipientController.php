<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecipientResource;
use Illuminate\Http\Request;

class InlineEncryptedRecipientController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['id' => 'required|string']);

        $recipient = user()->recipients()->findOrFail($request->id);

        if (! $recipient->fingerprint) {
            return response('You need to add a public key to this recipient before you can enable inline encryption', 422);
        }

        if ($recipient->protected_headers) {
            return response('You need to disable protected headers (hide subject) before you can enable inline encryption', 422);
        }

        $recipient->update(['inline_encryption' => true]);

        return new RecipientResource($recipient->load('aliases'));
    }

    public function destroy($id)
    {
        $recipient = user()->recipients()->findOrFail($id);

        $recipient->update(['inline_encryption' => false]);

        return response('', 204);
    }
}
