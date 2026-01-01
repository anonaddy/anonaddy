<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateDarkModeRequest;

class DarkModeController extends Controller
{
    public function update(UpdateDarkModeRequest $request)
    {
        if ($request->dark_mode) {
            user()->update(['dark_mode' => true]);
        } else {
            user()->update(['dark_mode' => false]);
        }

        return back()->with(['flash' => $request->dark_mode ? 'Dark Mode Enabled Successfully' : 'Dark Mode Disabled Successfully']);
    }
}
