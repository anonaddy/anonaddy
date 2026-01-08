<?php

namespace App\Http\Controllers\Auth;

use App\Facades\Webauthn;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FALaravel\Support\Authenticator;

class BackupCodeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('throttle:3,1')->only(['login', 'update']);
    }

    public function index(Request $request)
    {
        // If user has no 2FA methods enabled, redirect to home
        if (! $request->user()->hasAnyTwoFactorEnabled()) {
            return redirect('/');
        }

        // Check if user is already authenticated with any 2FA method
        $totpAuthenticator = app(Authenticator::class)->boot($request);
        $webauthnAuthenticated = Webauthn::check();

        if ($totpAuthenticator->isAuthenticated() || $webauthnAuthenticated) {
            return redirect('/');
        }

        return view('auth.backup_code');
    }

    public function login(Request $request)
    {
        $this->validate($request, [
            'backup_code' => 'required|string|min:40|max:40',
        ]);

        if (! Hash::check($request->backup_code, user()->two_factor_backup_code)) {
            return back()->withErrors([
                'backup_code' => __('The backup code was invalid.'),
            ]);
        }

        $twoFactor = app('pragmarx.google2fa');

        user()->update([
            'two_factor_enabled' => false,
            'webauthn_enabled' => false,
            'two_factor_secret' => $twoFactor->generateSecretKey(),
            'two_factor_backup_code' => null,
        ]);

        user()->webauthnKeys()->delete();

        if ($request->session()->has('intended_path')) {
            return redirect($request->session()->pull('intended_path'));
        }

        return redirect()->intended($request->redirectPath);
    }

    public function update(Request $request)
    {
        $request->validate([
            'current' => 'required|string|current_password',
        ]);

        user()->update([
            'two_factor_backup_code' => bcrypt($code = Str::random(40)),
        ]);

        return back()->with([
            'flash' => 'New Backup Code Generated Successfully',
            'regeneratedBackupCode' => $code,
        ]);
    }
}
