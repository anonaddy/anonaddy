<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WebauthnEnabledKeyController extends Controller
{
    public function store(Request $request)
    {
        $webauthnKey = user()->webauthnKeys()->findOrFail($request->id);

        $webauthnKey->enable();

        return response('', 201);
    }

    public function destroy($id)
    {
        $webauthnKey = user()->webauthnKeys()->findOrFail($id);

        $webauthnKey->disable();

        return response('', 204);
    }
}
