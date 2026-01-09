<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecipientResource;
use Illuminate\Http\Request;

class RemovePgpSignaturesRecipientController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['id' => 'required|string']);

        $recipient = user()->recipients()->findOrFail($request->id);

        $recipient->update(['remove_pgp_signatures' => true]);

        return new RecipientResource($recipient->loadCount('aliases'));
    }

    public function destroy($id)
    {
        $recipient = user()->recipients()->findOrFail($id);

        $recipient->update(['remove_pgp_signatures' => false]);

        return response('', 204);
    }
}
