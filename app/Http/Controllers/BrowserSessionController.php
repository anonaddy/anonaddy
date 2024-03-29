<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BrowserSessionController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:3,1')->only('destroy');
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'current' => 'required|string|current_password',
        ]);

        Auth::logoutOtherDevices($request->current);

        return back()->with(['flash' => 'Successfully logged out of other browser sessions!']);
    }
}
