<?php

namespace App\Http\Controllers;

use App\Http\Requests\DestroyAccountRequest;
use App\Jobs\DeleteAccount;
use Illuminate\Support\Facades\Hash;

class SettingController extends Controller
{
    public function show()
    {
        $twoFactor = app('pragmarx.google2fa');

        $qrCode = $twoFactor->getQRCodeInline(
            config('app.name'),
            user()->email,
            user()->two_factor_secret
        );

        return view('settings.show', [
            'user' => user(),
            'recipientOptions' => user()->verifiedRecipients,
            'authSecret' => user()->two_factor_secret,
            'qrCode' => $qrCode,
        ]);
    }

    public function destroy(DestroyAccountRequest $request)
    {
        if (! Hash::check($request->current_password_delete, user()->password)) {
            return back()->withErrors(['current_password_delete' => 'Incorrect password entered']);
        }

        DeleteAccount::dispatch(user());

        auth()->logout();
        $request->session()->invalidate();

        return redirect()->route('login')
            ->with(['status' => 'Account deleted successfully!']);
    }
}
