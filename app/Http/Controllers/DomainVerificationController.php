<?php

namespace App\Http\Controllers;

class DomainVerificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:1,1');
    }

    public function checkSending($id)
    {
        $domain = user()->domains()->findOrFail($id);

        return $domain->checkVerificationForSending();
    }
}
