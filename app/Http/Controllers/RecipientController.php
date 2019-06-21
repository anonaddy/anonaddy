<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRecipientRequest;
use App\Http\Resources\RecipientResource;

class RecipientController extends Controller
{
    public function index()
    {
        $recipients = user()->recipients()->with('aliases')->latest()->get();

        return view('recipients.index', [
            'recipients' => $recipients,
            'aliasesUsingDefault' => user()->aliasesUsingDefault
        ]);
    }

    public function store(StoreRecipientRequest $request)
    {
        $recipient = user()->recipients()->create(['email' => $request->email]);

        $recipient->sendEmailVerificationNotification();

        return new RecipientResource($recipient->fresh());
    }

    public function destroy($id)
    {
        if ($id === user()->default_recipient_id) {
            return response('', 403);
        }

        $recipient = user()->recipients()->findOrFail($id);

        $recipient->aliases()->detach();

        $recipient->delete();

        return response('', 204);
    }
}
