<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePersonalAccessTokenRequest;
use App\Http\Resources\PersonalAccessTokenResource;
use chillerlan\QRCode\QRCode;

class PersonalAccessTokenController extends Controller
{
    public function index()
    {
        return PersonalAccessTokenResource::collection(user()->tokens()->select(['id', 'tokenable_id', 'name', 'created_at', 'last_used_at', 'expires_at', 'updated_at', 'created_at'])->get());
    }

    public function store(StorePersonalAccessTokenRequest $request)
    {
        // day, week, month, year or null
        if ($request->expiration) {
            $method = 'add'.ucfirst($request->expiration);
            $expiration = now()->{$method}();
        } else {
            $expiration = null;
        }

        $token = user()->createToken($request->name, ['*'], $expiration);
        $accessToken = explode('|', $token->plainTextToken, 2)[1];

        return [
            'token' => new PersonalAccessTokenResource($token->accessToken),
            'accessToken' => $accessToken,
            'qrCode' => (new QRCode())->render(config('app.url').'|'.$accessToken),
        ];
    }

    public function destroy($id)
    {
        $token = user()->tokens()->findOrFail($id);

        $token->delete();

        return response('', 204);
    }
}
