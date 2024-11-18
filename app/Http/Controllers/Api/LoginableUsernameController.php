<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UsernameResource;
use Illuminate\Http\Request;

class LoginableUsernameController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['id' => 'required|string']);

        if (usesExternalAuthentication()) {
            return response('You cannot allow login because you\'re using external authentication', 403);
        }

        $username = user()->usernames()->findOrFail($request->id);

        $username->allowLogin();

        return new UsernameResource($username->load('defaultRecipient')->loadCount('aliases'));
    }

    public function destroy($id)
    {
        $username = user()->usernames()->findOrFail($id);

        if ($id === user()->default_username_id) {
            return response('You cannot disallow login for your default username', 403);
        }

        $username->disallowLogin();

        return response('', 204);
    }
}
