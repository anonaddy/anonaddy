<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdditionalUsernameResource;
use Illuminate\Http\Request;

class CatchAllAdditionalUsernameController extends Controller
{
    public function store(Request $request)
    {
        $username = user()->additionalUsernames()->findOrFail($request->id);

        $username->enableCatchAll();

        return new AdditionalUsernameResource($username);
    }

    public function destroy($id)
    {
        $username = user()->additionalUsernames()->findOrFail($id);

        $username->disableCatchAll();

        return response('', 204);
    }
}
