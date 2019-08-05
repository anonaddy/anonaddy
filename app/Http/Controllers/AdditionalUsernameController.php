<?php

namespace App\Http\Controllers;

use App\DeletedUsername;
use App\Http\Requests\StoreAdditionalUsernameRequest;
use App\Http\Requests\UpdateAdditionalUsernameRequest;
use App\Http\Resources\AdditionalUsernameResource;

class AdditionalUsernameController extends Controller
{
    public function index()
    {
        return view('usernames.index', [
            'usernames' => user()->additionalUsernames()->latest()->get()
        ]);
    }

    public function store(StoreAdditionalUsernameRequest $request)
    {
        if (user()->hasReachedAdditionalUsernameLimit()) {
            return response('', 403);
        }

        $username = user()->additionalUsernames()->create(['username' => $request->username]);

        user()->increment('username_count');

        return new AdditionalUsernameResource($username->fresh());
    }

    public function update(UpdateAdditionalUsernameRequest $request, $id)
    {
        $username = user()->additionalUsernames()->findOrFail($id);

        $username->update(['description' => $request->description]);

        return new AdditionalUsernameResource($username);
    }

    public function destroy($id)
    {
        $username = user()->additionalUsernames()->findOrFail($id);

        DeletedUsername::create(['username' => $username->username]);

        $username->delete();

        return response('', 204);
    }
}
