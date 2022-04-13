<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecipientResource;
use Illuminate\Http\Request;

class AllowedRecipientController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['id' => 'required|string']);

        $recipient = user()->recipients()->findOrFail($request->id);

        $recipient->update(['can_reply_send' => true]);

        return new RecipientResource($recipient->load('aliases'));
    }

    public function destroy($id)
    {
        $recipient = user()->recipients()->findOrFail($id);

        $recipient->update(['can_reply_send' => false]);

        return response('', 204);
    }
}
