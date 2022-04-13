<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UsernameResource;
use Illuminate\Http\Request;

class CatchAllUsernameController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['id' => 'required|string']);

        $username = user()->usernames()->findOrFail($request->id);

        $username->enableCatchAll();

        return new UsernameResource($username->load(['aliases', 'defaultRecipient']));
    }

    public function destroy($id)
    {
        $username = user()->usernames()->findOrFail($id);

        $username->disableCatchAll();

        return response('', 204);
    }
}
