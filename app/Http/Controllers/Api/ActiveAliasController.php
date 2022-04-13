<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AliasResource;
use Illuminate\Http\Request;

class ActiveAliasController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['id' => 'required|string']);

        $alias = user()->aliases()->withTrashed()->findOrFail($request->id);

        if ($alias->trashed()) {
            return response('You need to restore this alias before you can activate it', 422);
        }

        $alias->activate();

        return new AliasResource($alias->load('recipients'));
    }

    public function destroy($id)
    {
        $alias = user()->aliases()->withTrashed()->findOrFail($id);

        $alias->deactivate();

        return response('', 204);
    }
}
