<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAliasRecipientRequest;
use App\Http\Resources\AliasResource;

class AliasRecipientController extends Controller
{
    public function store(StoreAliasRecipientRequest $request)
    {
        $alias = user()->aliases()->withTrashed()->findOrFail($request->alias_id);

        $alias->recipients()->sync($request->recipient_ids);

        return new AliasResource($alias->refresh()->load('recipients'));
    }
}
