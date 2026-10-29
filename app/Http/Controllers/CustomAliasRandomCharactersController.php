<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCustomAliasRandomCharactersRequest;

class CustomAliasRandomCharactersController extends Controller
{
    public function update(UpdateCustomAliasRandomCharactersRequest $request)
    {
        $user = user();
        $user->custom_alias_random_characters = $request->boolean('enabled');
        $user->custom_alias_random_length = $request->integer('length');
        $user->custom_alias_random_charset = $request->validated('charset');
        $user->save();

        return back()->with(['flash' => 'Custom Alias Random Characters Updated Successfully']);
    }
}
