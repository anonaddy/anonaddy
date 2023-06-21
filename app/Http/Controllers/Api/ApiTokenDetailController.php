<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ApiTokenDetailController extends Controller
{
    public function show(Request $request)
    {
        $token = $request->user()->currentAccessToken();

        if (! $token) {
            return response('Current token could not be found', 404);
        }

        return response()->json([
            'name' => $token->name,
            'created_at' => $token->created_at?->toDateTimeString(),
            'expires_at' => $token->expires_at?->toDateTimeString(),
        ]);
    }
}
