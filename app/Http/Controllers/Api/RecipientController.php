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
        $data = ['email' => strtolower($request->email)];

        if (config('anonaddy.auto_verify_new_recipients')) {
            $data['email_verified_at'] = now();
        }

        $recipient = user()->recipients()->create($data);

        if (! config('anonaddy.auto_verify_new_recipients')) {
            $recipient->sendEmailVerificationNotification();
        }

        return new RecipientResource($recipient->refresh()->load('aliases'));
    }

    public function destroy($id)
    {
        if ($id === user()->default_recipient_id) {
            return response('You cannot delete your default recipient', 403);
        }

        $recipient = user()->recipients()->findOrFail($id);

        $recipient->delete();

        return response('', 204);
    }
}
