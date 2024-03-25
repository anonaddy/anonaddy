<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\EnableTwoFactorAuthRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PragmaRX\Google2FALaravel\Support\Authenticator;

class TwoFactorAuthController extends Controller
{
    protected $twoFactor;

    protected $authenticator;

    public function __construct(Request $request)
    {
        $this->middleware('throttle:3,1')->only(['store', 'update', 'destroy']);
        $this->twoFactor = app('pragmarx.google2fa');
        $this->authenticator = app(Authenticator::class)->boot($request);
    }

    public function index()
    {
        return redirect('/');
    }

    public function store(EnableTwoFactorAuthRequest $request)
    {
        if (! $this->twoFactor->verifyKey(user()->two_factor_secret, $request->two_factor_token, config('google2fa.window'))) {
            return redirect(url()->previous().'#two-factor')->withErrors(['two_factor_token' => 'The token you entered was incorrect']);
        }

        user()->webauthnKeys()->delete();

        user()->update([
            'two_factor_enabled' => true,
            'two_factor_backup_code' => bcrypt($code = Str::random(40)),
        ]);

        $this->authenticator->login();

        return back()->with(['backupCode' => $code]);
    }

    public function update()
    {
        if (user()->two_factor_enabled) {
            return back()->withErrors(['regenerate_2fa' => 'You must disable 2FA before you can regenerate your secret key']);
        }

        user()->update(['two_factor_secret' => $this->twoFactor->generateSecretKey()]);

        return back()->with(['flash' => '2FA Secret Successfully Regenerated']);
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'current' => 'required|string|current_password',
        ]);

        user()->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => $this->twoFactor->generateSecretKey(),
        ]);

        $this->authenticator->logout();

        return back()->with(['flash' => '2FA Disabled Successfully']);
    }

    public function authenticateTwoFactor(Request $request)
    {
        if ($request->session()->has('intended_path')) {
            return redirect($request->session()->pull('intended_path'));
        }

        redirect()->intended($request->redirectPath);
    }
}
