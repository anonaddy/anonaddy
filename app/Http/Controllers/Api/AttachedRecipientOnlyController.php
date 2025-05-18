<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AliasResource;
use Illuminate\Http\Request;

class AttachedRecipientOnlyController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['id' => 'required|string']);

        $alias = user()->aliases()->withTrashed()->findOrFail($request->id);

        $alias->update(['attached_recipients_only' => true]);

        return new AliasResource($alias->load('recipients'));
    }

    public function destroy($id)
    {
        $alias = user()->aliases()->withTrashed()->findOrFail($id);

        $alias->update(['attached_recipients_only' => false]);

        return response('', 204);
    }
}
