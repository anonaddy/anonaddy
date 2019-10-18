<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDomainRequest;
use App\Http\Requests\UpdateDomainRequest;
use App\Http\Resources\DomainResource;

class DomainController extends Controller
{
    public function index()
    {
        return DomainResource::collection(user()->domains()->with(['aliases', 'defaultRecipient'])->latest()->get());
    }

    public function show($id)
    {
        $domain = user()->domains()->findOrFail($id);

        return new DomainResource($domain->load(['aliases', 'defaultRecipient']));
    }

    public function store(StoreDomainRequest $request)
    {
        $domain = user()->domains()->create(['domain' => $request->domain]);

        $domain->checkVerification();

        return new DomainResource($domain->refresh()->load(['aliases', 'defaultRecipient']));
    }

    public function update(UpdateDomainRequest $request, $id)
    {
        $domain = user()->domains()->findOrFail($id);

        $domain->update(['description' => $request->description]);

        return new DomainResource($domain->refresh()->load(['aliases', 'defaultRecipient']));
    }

    public function destroy($id)
    {
        $domain = user()->domains()->findOrFail($id);

        $domain->delete();

        return response('', 204);
    }
}
