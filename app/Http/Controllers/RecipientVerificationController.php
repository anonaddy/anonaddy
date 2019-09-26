<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RecipientVerificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:1,1');
    }

    public function resend(Request $request)
    {
        $recipient = user()->recipients()->findOrFail($request->recipient_id);

        if ($recipient->hasVerifiedEmail()) {
            return response('Email already verified', 404);
        }

        $recipient->sendEmailVerificationNotification();

        return response('', 200);
    }
}
