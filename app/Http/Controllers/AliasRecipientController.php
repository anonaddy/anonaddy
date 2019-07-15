<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAliasRecipientRequest;
use App\Http\Resources\AliasResource;

class AliasRecipientController extends Controller
{
    public function store(StoreAliasRecipientRequest $request)
    {
        $alias = user()->aliases()->findOrFail($request->alias_id);

        $alias->recipients()->sync($request->recipient_ids);

        return new AliasResource($alias);
    }
}
