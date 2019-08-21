<?php

namespace App\Http\Controllers;

use App\Http\Resources\DomainResource;

class DomainVerificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:1,1');
    }

    public function recheck($id)
    {
        $domain = user()->domains()->findOrFail($id);

        if ($domain->isVerified()) {
            return response('Domain already verified', 404);
        }

        $domain->checkVerification();

        return new DomainResource($domain->fresh());
    }
}
