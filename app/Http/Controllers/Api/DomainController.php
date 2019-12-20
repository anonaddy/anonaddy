<?php

namespace App\Http\Controllers\Api;

use App\Domain;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDomainRequest;
use App\Http\Requests\UpdateDomainRequest;
use App\Http\Resources\DomainResource;

class DomainController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:6,1')->only('store');
    }

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
        $domain = new Domain();
        $domain->domain = $request->domain;

        if (! $domain->checkVerification()) {
            return response('Verification record not found, please add the following TXT record to your domain: aa-verify=' . sha1(config('anonaddy.secret') . user()->id), 404);
        }

        user()->domains()->save($domain);

        $domain->markDomainAsVerified();

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
