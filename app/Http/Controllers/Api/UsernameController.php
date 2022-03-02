<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUsernameRequest;
use App\Http\Requests\UpdateUsernameRequest;
use App\Http\Resources\UsernameResource;

class UsernameController extends Controller
{
    public function index()
    {
        return UsernameResource::collection(user()->usernames()->with(['aliases', 'defaultRecipient'])->latest()->get());
    }

    public function show($id)
    {
        $username = user()->usernames()->findOrFail($id);

        return new UsernameResource($username->load(['aliases', 'defaultRecipient']));
    }

    public function store(StoreUsernameRequest $request)
    {
        if (user()->hasReachedUsernameLimit()) {
            return response('', 403);
        }

        $username = user()->usernames()->create(['username' => $request->username]);

        user()->increment('username_count');

        return new UsernameResource($username->refresh()->load(['aliases', 'defaultRecipient']));
    }

    public function update(UpdateUsernameRequest $request, $id)
    {
        $username = user()->usernames()->findOrFail($id);

        $username->update(['description' => $request->description]);

        return new UsernameResource($username->refresh()->load(['aliases', 'defaultRecipient']));
    }

    public function destroy($id)
    {
        if ($id === user()->default_username_id) {
            return response('You cannot delete your default username', 403);
        }

        $username = user()->usernames()->findOrFail($id);

        $username->delete();

        return response('', 204);
    }
}
