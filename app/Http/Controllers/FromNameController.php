<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAccountFromNameRequest;

class FromNameController extends Controller
{
    public function update(UpdateAccountFromNameRequest $request)
    {
        user()->update(['from_name' => $request->from_name]);

        return back()->with(['flash' => 'From Name Updated Successfully']);
    }
}
