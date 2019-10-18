<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecipientResource;
use Illuminate\Http\Request;

class EncryptedRecipientController extends Controller
{
    public function store(Request $request)
    {
        $recipient = user()->recipients()->findOrFail($request->id);

        $recipient->update(['should_encrypt' => true]);

        return new RecipientResource($recipient);
    }

    public function destroy($id)
    {
        $recipient = user()->recipients()->findOrFail($id);

        $recipient->update(['should_encrypt' => false]);

        return new RecipientResource($recipient);
    }
}
