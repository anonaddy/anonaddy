<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecipientResource;
use Illuminate\Http\Request;

class ProtectedHeadersRecipientController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['id' => 'required|string']);

        $recipient = user()->recipients()->findOrFail($request->id);

        if (! $recipient->fingerprint) {
            return response('You need to add a public key to this recipient before you can enable protected headers (hide subject)', 422);
        }

        if ($recipient->inline_encryption) {
            return response('You need to disable inline encryption before you can enable protected headers (hide subject)', 422);
        }

        $recipient->update(['protected_headers' => true]);

        return new RecipientResource($recipient->load('aliases'));
    }

    public function destroy($id)
    {
        $recipient = user()->recipients()->findOrFail($id);

        $recipient->update(['protected_headers' => false]);

        return response('', 204);
    }
}
