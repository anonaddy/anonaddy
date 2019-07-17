<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEmailSubjectRequest;

class EmailSubjectController extends Controller
{
    public function update(UpdateEmailSubjectRequest $request)
    {
        user()->update(['email_subject' => $request->email_subject]);

        return back()->with(['status' => 'Email Subject Updated Successfully']);
    }
}
