<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateDefaultAliasFormatRequest;

class DefaultAliasFormatController extends Controller
{
    public function update(UpdateDefaultAliasFormatRequest $request)
    {
        user()->default_alias_format = $request->format;
        user()->save();

        return back()->with(['status' => 'Default Alias Format Updated Successfully']);
    }
}
