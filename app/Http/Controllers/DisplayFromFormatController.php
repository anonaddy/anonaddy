<?php

namespace App\Http\Controllers;

use App\Enums\DisplayFromFormat;
use App\Http\Requests\UpdateDisplayFromFormatRequest;

class DisplayFromFormatController extends Controller
{
    public function update(UpdateDisplayFromFormatRequest $request)
    {
        user()->display_from_format = DisplayFromFormat::from($request->format);
        user()->save();

        return back()->with(['flash' => 'Default Alias Format Updated Successfully']);
    }
}
