<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAliasLabelRequest;
use App\Http\Resources\AliasResource;

class AliasLabelController extends Controller
{
    public function store(StoreAliasLabelRequest $request)
    {
        $alias = user()->aliases()->withTrashed()->findOrFail($request->alias_id);

        $alias->syncLabels($request->label_ids);

        return new AliasResource($alias->refresh()->load('labels'));
    }
}
