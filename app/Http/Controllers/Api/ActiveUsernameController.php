<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UsernameResource;
use Illuminate\Http\Request;

class ActiveUsernameController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['id' => 'required|string']);

        $username = user()->usernames()->findOrFail($request->id);

        $username->activate();

        return new UsernameResource($username->load(['aliases', 'defaultRecipient']));
    }

    public function destroy($id)
    {
        $username = user()->usernames()->findOrFail($id);

        $username->deactivate();

        return response('', 204);
    }
}
