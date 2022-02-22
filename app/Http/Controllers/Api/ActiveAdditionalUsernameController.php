<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdditionalUsernameResource;
use Illuminate\Http\Request;

class ActiveAdditionalUsernameController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['id' => 'required|string']);

        $username = user()->additionalUsernames()->findOrFail($request->id);

        $username->activate();

        return new AdditionalUsernameResource($username);
    }

    public function destroy($id)
    {
        $username = user()->additionalUsernames()->findOrFail($id);

        $username->deactivate();

        return response('', 204);
    }
}
