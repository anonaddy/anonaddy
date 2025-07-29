<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Rules\VerifiedRecipientId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ResendableFailedDeliveryController extends Controller
{
    public function index(Request $request, $id)
    {
        Validator::make(['id' => $id], [
            'id' => 'required|uuid',
        ])->validate();

        $request->validate([
            'recipient_ids' => [
                'bail',
                'nullable',
                'array',
                'max:10',
                new VerifiedRecipientId,
            ],
        ]);

        $failedDelivery = user()->failedDeliveries()->findOrFail($id);

        $recipientIds = $request->has('recipient_ids') ? $request->recipient_ids : null;

        if ($failedDelivery->resend($recipientIds) !== true) {
            return response('Failed to resend email', 500);
        }

        return response('', 204);
    }
}
