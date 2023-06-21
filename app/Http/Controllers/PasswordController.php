<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    public function update(UpdatePasswordRequest $request)
    {
        if (! Hash::check($request->current, user()->password)) {
            return redirect(url()->previous().'#update-password')->withErrors(['current' => 'Current password incorrect']);
        }

        // Log out of other sessions
        Auth::logoutOtherDevices($request->current);

        user()->password = Hash::make($request->password);
        user()->save();

        return back()->with(['status' => 'Password Updated Successfully']);
    }
}
