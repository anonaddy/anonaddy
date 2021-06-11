<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexRecipientRequest;
use App\Http\Requests\StoreRecipientRequest;
use App\Http\Resources\RecipientResource;

class RecipientController extends Controller
{
    public function index(IndexRecipientRequest $request)
    {
        $recipients = user()->recipients()->with('aliases')->latest();

        if ($request->input('filter.verified') === 'true') {
            $recipients->verified();
        }

        if ($request->input('filter.verified') === 'false') {
            $recipients->verified('false');
        }

        return RecipientResource::collection($recipients->get());
    }

    public function show($id)
    {
        $recipient = user()->recipients()->findOrFail($id);

        return new RecipientResource($recipient->load('aliases'));
    }

    public function store(StoreRecipientRequest $request)
    {
        $recipient = user()->recipients()->create(['email' => strtolower($request->email)]);

        $recipient->sendEmailVerificationNotification();

        return new RecipientResource($recipient->refresh()->load('aliases'));
    }

    public function destroy($id)
    {
        if ($id === user()->default_recipient_id) {
            return response('', 403);
        }

        $recipient = user()->recipients()->findOrFail($id);

        $recipient->delete();

        return response('', 204);
    }
}
