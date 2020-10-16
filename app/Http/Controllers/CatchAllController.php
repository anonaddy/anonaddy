<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCatchAllRequest;

class CatchAllController extends Controller
{
    public function update(UpdateCatchAllRequest $request)
    {
        if ($request->catch_all) {
            user()->enableCatchAll();
        } else {
            user()->disableCatchAll();
        }

        return back()->with(['status' => $request->catch_all ? 'Catch-All Enabled Successfully' : 'Catch-All Disabled Successfully']);
    }
}
