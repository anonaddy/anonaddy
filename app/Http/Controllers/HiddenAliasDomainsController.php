<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateHiddenAliasDomainsRequest;

class HiddenAliasDomainsController extends Controller
{
    public function update(UpdateHiddenAliasDomainsRequest $request)
    {
        $defaultAliasDomain = user()->default_alias_domain;

        user()->hidden_alias_domains = collect($request->validated('hidden_domains'))
            ->unique()
            ->reject(fn (string $domain) => $domain === $defaultAliasDomain)
            ->values()
            ->all();
        user()->save();

        return back()->with(['flash' => 'Alias Domain Picker Updated Successfully']);
    }
}
