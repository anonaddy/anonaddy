<?php

namespace App\Http\Controllers;

class RecipientVerificationController extends Controller
{
    public function resend($id)
    {
        $recipient = user()->recipients()->findOrFail($id);

        if ($recipient->hasVerifiedEmail()) {
            return response('Email already verified', 404);
        }

        $recipient->sendEmailVerificationNotification();

        return response('', 200);
    }
}
