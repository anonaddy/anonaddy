<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    public function update(UpdatePasswordRequest $request)
    {
        if (!Hash::check($request->current, user()->password)) {
            return back()->withErrors(['current' => 'Current password incorrect']);
        }

        user()->password = Hash::make($request->password);
        user()->save();

        return back()->with(['status' => 'Password Updated Successfully']);
    }
}
