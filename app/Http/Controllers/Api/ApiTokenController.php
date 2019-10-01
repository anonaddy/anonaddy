<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;

class ApiTokenController extends Controller
{
    public function update()
    {
        $token = Str::random(60);

        user()->forceFill([
            'api_token' => hash('sha256', $token),
        ])->save();

        return response()->json([
            'token' => $token
        ]);
    }

    public function destroy()
    {
        user()->forceFill([
            'api_token' => null,
        ])->save();

        return response('', 204);
    }
}
