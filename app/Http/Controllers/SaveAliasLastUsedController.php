<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSaveAliasLastUsedRequest;

class SaveAliasLastUsedController extends Controller
{
    public function update(UpdateSaveAliasLastUsedRequest $request)
    {
        if ($request->save_alias_last_used) {
            user()->update(['save_alias_last_used' => true]);
        } else {
            user()->update(['save_alias_last_used' => false]);
        }

        return back()->with(['flash' => $request->save_alias_last_used ? 'Save Alias Last Used At Enabled Successfully' : 'Save Alias Last Used At Disabled Successfully']);
    }
}
