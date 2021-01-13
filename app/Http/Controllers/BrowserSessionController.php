<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BrowserSessionController extends Controller
{
    public function destroy(Request $request)
    {
        $request->validate([
            'current_password_sesssions' => 'password',
        ]);

        Auth::logoutOtherDevices($request->current_password_sesssions);

        return back()->with(['status' => 'Successfully logged out of other browser sessions!']);
    }
}
