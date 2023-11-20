<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateDefaultAliasDomainRequest;

class DefaultAliasDomainController extends Controller
{
    public function update(UpdateDefaultAliasDomainRequest $request)
    {
        user()->default_alias_domain = $request->domain;
        user()->save();

        return back()->with(['flash' => 'Default Alias Domain Updated Successfully']);
    }
}
