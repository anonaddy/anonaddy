<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DomainResource;
use Illuminate\Http\Request;

class CatchAllDomainController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['id' => 'required|string']);

        $domain = user()->domains()->findOrFail($request->id);

        $domain->enableCatchAll();

        return new DomainResource($domain->load(['aliases', 'defaultRecipient']));
    }

    public function destroy($id)
    {
        $domain = user()->domains()->findOrFail($id);

        $domain->disableCatchAll();

        return response('', 204);
    }
}
