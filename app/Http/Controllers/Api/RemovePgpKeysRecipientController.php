<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecipientResource;
use Illuminate\Http\Request;

class RemovePgpKeysRecipientController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['id' => 'required|string']);

        $recipient = user()->recipients()->findOrFail($request->id);

        $recipient->update(['remove_pgp_keys' => true]);

        return new RecipientResource($recipient->loadCount('aliases'));
    }

    public function destroy($id)
    {
        $recipient = user()->recipients()->findOrFail($id);

        $recipient->update(['remove_pgp_keys' => false]);

        return response('', 204);
    }
}
