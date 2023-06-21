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

        // Check MX records separately
        if (! $domain->checkMxRecords()) {
            return response()->json([
                'success' => false,
                'message' => 'MX record not found or does not have correct priority. This could be due to DNS caching, please try again later.',
            ]);
        }

        return $domain->checkVerificationForSending();
    }
}
