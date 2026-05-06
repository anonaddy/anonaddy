<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Sanctum\PersonalAccessToken;

class ApiTokenDetailController extends Controller
{
    public function show(Request $request): JsonResponse|Response
    {
        $token = $request->user()->currentAccessToken();

        if (! $token instanceof PersonalAccessToken) {
            return response('Current token could not be found', 404);
        }

        return response()->json([
            'name' => $token->name,
            'created_at' => $token->created_at?->toDateTimeString(),
            'expires_at' => $token->expires_at?->toDateTimeString(),
        ]);
    }
}
