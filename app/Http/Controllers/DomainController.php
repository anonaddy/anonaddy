<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDomainRequest;
use App\Http\Requests\UpdateDomainRequest;
use App\Http\Resources\DomainResource;

class DomainController extends Controller
{
    public function index()
    {
        return view('domains.index', [
            'domains' => user()->domains()->with('aliases')->latest()->get()
        ]);
    }

    public function store(StoreDomainRequest $request)
    {
        $domain = user()->domains()->create(['domain' => $request->domain]);

        $domain->checkVerification();

        return new DomainResource($domain->fresh());
    }

    public function update(UpdateDomainRequest $request, $id)
    {
        $domain = user()->domains()->findOrFail($id);

        $domain->update(['description' => $request->description]);

        return new DomainResource($domain);
    }

    public function destroy($id)
    {
        $domain = user()->domains()->findOrFail($id);

        $domain->delete();

        return response('', 204);
    }
}
