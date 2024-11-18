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
        return UsernameResource::collection(user()->usernames()->with('defaultRecipient')->withCount('aliases')->latest()->get());
    }

    public function show($id)
    {
        $username = user()->usernames()->findOrFail($id);

        return new UsernameResource($username->load('defaultRecipient')->loadCount('aliases'));
    }

    public function store(StoreUsernameRequest $request)
    {
        if (user()->hasReachedUsernameLimit()) {
            return response('', 403);
        }

        $username = user()->usernames()->create(['username' => $request->username, 'can_login' => !usesExternalAuthentication()]);

        user()->increment('username_count');

        return new UsernameResource($username->refresh()->load('defaultRecipient')->loadCount('aliases'));
    }

    public function update(UpdateUsernameRequest $request, $id)
    {
        $username = user()->usernames()->findOrFail($id);

        if ($request->has('description')) {
            $username->description = $request->description;
        }

        if ($request->has('from_name')) {
            $username->from_name = $request->from_name;
        }

        if ($request->has('auto_create_regex')) {
            $username->auto_create_regex = $request->auto_create_regex;
        }

        $username->save();

        return new UsernameResource($username->refresh()->load('defaultRecipient')->loadCount('aliases'));
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
