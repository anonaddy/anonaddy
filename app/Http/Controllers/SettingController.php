<?php

namespace App\Http\Controllers;

use App\Http\Requests\DestroyAccountRequest;
use App\Http\Resources\PersonalAccessTokenResource;
use App\Jobs\DeleteAccount;
use Illuminate\Http\Request;
use Inertia\Inertia;
use LaravelWebauthn\Facades\Webauthn;

class SettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:3,1')->only('destroy');
    }

    public function show()
    {
        return Inertia::render('Settings/General', [
            'defaultAliasDomain' => user()->default_alias_domain,
            'defaultAliasFormat' => user()->default_alias_format,
            'loginRedirect' => user()->login_redirect->value,
            'displayFromFormat' => user()->display_from_format->value,
            'useReplyTo' => user()->use_reply_to,
            'storeFailedDeliveries' => user()->store_failed_deliveries,
            'saveAliasLastUsed' => user()->save_alias_last_used,
            'fromName' => user()->from_name ?? '',
            'emailSubject' => user()->email_subject ?? '',
            'bannerLocation' => user()->banner_location,
            'domainOptions' => user()->domainOptions(),
        ]);
    }

    public function security(Request $request)
    {
        $twoFactor = app('pragmarx.google2fa');

        $qrCode = $twoFactor->getQRCodeInline(
            config('app.name'),
            user()->email,
            user()->two_factor_secret
        );

        // User has either webauthn or TOTP 2FA enabled
        $hasTwoFactor = Webauthn::enabled(user()) || user()->two_factor_enabled;

        return Inertia::render('Settings/Security', [
            'authSecret' => $hasTwoFactor ? null : user()->two_factor_secret,
            'qrCode' => $hasTwoFactor ? null : $qrCode,
            'regeneratedBackupCode' => $request->session()->get('regeneratedBackupCode', null),
            'backupCode' => $request->session()->get('backupCode', null),
            'twoFactorEnabled' => user()->two_factor_enabled,
            'webauthnEnabled' => Webauthn::enabled(user()),
            'initialKeys' => user()->webauthnKeys()->latest()->select(['id', 'name', 'enabled', 'created_at'])->get()->values(),
        ]);
    }

    public function api()
    {
        return Inertia::render('Settings/Api', [
            'initialTokens' => PersonalAccessTokenResource::collection(user()->tokens()->select(['id', 'tokenable_id', 'name', 'created_at', 'last_used_at', 'expires_at', 'updated_at', 'created_at'])->get()),
        ]);
    }

    public function data()
    {
        return Inertia::render('Settings/Data', [
            'totalAliasesCount' => user()->allAliases()->count(),
            'domainsCount' => user()->domains()->count(),
        ]);
    }

    public function account()
    {
        return Inertia::render('Settings/Account');
    }

    public function destroy(DestroyAccountRequest $request)
    {
        DeleteAccount::dispatch(user());

        auth()->logout();
        $request->session()->invalidate();

        return Inertia::location(route('login'));
    }
}
