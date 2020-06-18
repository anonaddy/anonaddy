<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AliasResource;
use Illuminate\Http\Request;

class ActiveAliasController extends Controller
{
    public function store(Request $request)
    {
        $alias = user()->aliases()->withTrashed()->findOrFail($request->id);

        $alias->activate();

        return new AliasResource($alias);
    }

    public function destroy($id)
    {
        $alias = user()->aliases()->withTrashed()->findOrFail($id);

        $alias->deactivate();

        return response('', 204);
    }
}
