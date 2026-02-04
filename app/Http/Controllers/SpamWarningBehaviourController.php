<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSpamWarningBehaviourRequest;

class SpamWarningBehaviourController extends Controller
{
    public function update(UpdateSpamWarningBehaviourRequest $request)
    {
        user()->update(['spam_warning_behaviour' => $request->spam_warning_behaviour]);

        return back()->with(['flash' => 'Spam / DMARC warning preference updated']);
    }
}
