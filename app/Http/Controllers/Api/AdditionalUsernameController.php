<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdditionalUsernameRequest;
use App\Http\Requests\UpdateAdditionalUsernameRequest;
use App\Http\Resources\AdditionalUsernameResource;
use App\Models\DeletedUsername;

class AdditionalUsernameController extends Controller
{
    public function index()
    {
        return AdditionalUsernameResource::collection(user()->additionalUsernames()->with(['aliases', 'defaultRecipient'])->latest()->get());
    }

    public function show($id)
    {
        $username = user()->additionalUsernames()->findOrFail($id);

        return new AdditionalUsernameResource($username->load(['aliases', 'defaultRecipient']));
    }

    public function store(StoreAdditionalUsernameRequest $request)
    {
        if (user()->hasReachedAdditionalUsernameLimit()) {
            return response('', 403);
        }

        $username = user()->additionalUsernames()->create(['username' => $request->username]);

        user()->increment('username_count');

        return new AdditionalUsernameResource($username->refresh()->load(['aliases', 'defaultRecipient']));
    }

    public function update(UpdateAdditionalUsernameRequest $request, $id)
    {
        $username = user()->additionalUsernames()->findOrFail($id);

        $username->update(['description' => $request->description]);

        return new AdditionalUsernameResource($username->refresh()->load(['aliases', 'defaultRecipient']));
    }

    public function destroy($id)
    {
        $username = user()->additionalUsernames()->findOrFail($id);

        DeletedUsername::create(['username' => $username->username]);

        $username->delete();

        return response('', 204);
    }
}
