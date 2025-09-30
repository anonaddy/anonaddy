<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WebauthnEnabledKeyController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:3,1')->only('destroy');
    }

    public function store(Request $request)
    {
        $webauthnKey = user()->webauthnKeys()->findOrFail($request->id);

        $webauthnKey->enable();

        if (! user()->webauthn_enabled) {
            user()->update(['webauthn_enabled' => true]);
        }

        return response('', 201);
    }

    public function destroy(Request $request, $id)
    {
        $request->validate([
            'current' => 'required|string|current_password',
        ]);

        $webauthnKey = user()->webauthnKeys()->findOrFail($id);

        $webauthnKey->disable();

        // If it is last enabled key then set webauthn_enabled to false on user model too
        if (user()->webauthnKeys()->where('enabled', true)->doesntExist()) {
            user()->update(['webauthn_enabled' => false]);
        }

        return response('', 204);
    }
}
