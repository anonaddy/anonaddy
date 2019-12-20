<?php

namespace App\Http\Controllers;

class DomainVerificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:6,1');
    }

    public function checkSending($id)
    {
        $domain = user()->domains()->findOrFail($id);

        if ($domain->isVerifiedForSending()) {
            return response('Domain already verified for sending', 404);
        }

        return $domain->checkVerificationForSending();
    }
}
