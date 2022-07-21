<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePersonalAccessTokenRequest;
use App\Http\Resources\PersonalAccessTokenResource;

class PersonalAccessTokenController extends Controller
{
    public function index()
    {
        return PersonalAccessTokenResource::collection(user()->tokens);
    }

    public function store(StorePersonalAccessTokenRequest $request)
    {
        $token = user()->createToken($request->name);

        return [
            'token' => new PersonalAccessTokenResource($token->accessToken),
            'accessToken' => explode('|', $token->plainTextToken, 2)[1]
        ];
    }

    public function destroy($id)
    {
        $token = user()->tokens()->findOrFail($id);

        $token->delete();

        return response('', 204);
    }
}
