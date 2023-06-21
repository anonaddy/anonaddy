<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUseReplyToRequest;

class UseReplyToController extends Controller
{
    public function update(UpdateUseReplyToRequest $request)
    {
        if ($request->use_reply_to) {
            user()->update(['use_reply_to' => true]);
        } else {
            user()->update(['use_reply_to' => false]);
        }

        return back()->with(['status' => $request->use_reply_to ? 'Use Reply To Enabled Successfully' : 'Use Reply To Disabled Successfully']);
    }
}
