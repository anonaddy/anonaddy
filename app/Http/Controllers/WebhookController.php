<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateWebhookRequest;

class WebhookController extends Controller
{
    public function update(UpdateWebhookRequest $request)
    {
        user()->update([
            'webhook_url' => $request->webhook_url,
            'signing_key' => $request->signing_key,
        ]);

        return back()->with(['flash' => 'Update Webhook Successfully']);
    }
}
