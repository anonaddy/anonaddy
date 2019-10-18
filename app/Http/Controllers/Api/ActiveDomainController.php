<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DomainResource;
use Illuminate\Http\Request;

class ActiveDomainController extends Controller
{
    public function store(Request $request)
    {
        $domain = user()->domains()->findOrFail($request->id);

        $domain->activate();

        return new DomainResource($domain);
    }

    public function destroy($id)
    {
        $domain = user()->domains()->findOrFail($id);

        $domain->deactivate();

        return new DomainResource($domain);
    }
}
