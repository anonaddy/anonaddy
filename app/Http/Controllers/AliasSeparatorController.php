<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAliasSeparatorRequest;

class AliasSeparatorController extends Controller
{
    public function update(UpdateAliasSeparatorRequest $request)
    {
        user()->alias_separator = $request->separator;
        user()->save();

        return back()->with(['flash' => 'Alias Separator Updated Successfully']);
    }
}
