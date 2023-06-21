<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateFromNameRequest;

class FromNameController extends Controller
{
    public function update(UpdateFromNameRequest $request)
    {
        user()->update(['from_name' => $request->from_name]);

        return back()->with(['status' => 'From Name Updated Successfully']);
    }
}
