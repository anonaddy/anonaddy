<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBannerLocationRequest;

class BannerLocationController extends Controller
{
    public function update(UpdateBannerLocationRequest $request)
    {
        user()->update(['banner_location' => $request->banner_location]);

        return back()->with(['status' => 'Location Updated Successfully']);
    }
}
