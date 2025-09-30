<?php

namespace App\Services;

use App\Models\WebauthnKey;
use Illuminate\Contracts\Auth\Authenticatable as User;
use LaravelWebauthn\Services\Webauthn as ServicesWebauthn;
use PragmaRX\Google2FALaravel\Support\Authenticator;

class Webauthn extends ServicesWebauthn
{
    /**
     * Test if the user has one or more webauthn key.
     */
    public static function enabled(User $user): bool
    {
        return static::webauthnEnabled() && static::hasKey($user);
    }

    /**
     * Detect if user has a key that is enabled.
     */
    public static function hasKey(User $user): bool
    {
        return WebauthnKey::where('user_id', $user->getAuthIdentifier())->where('enabled', true)->count() > 0;
    }

    /**
     * Test if the user can register a new key.
     */
    public static function canRegister(User $user): bool
    {
        $totpAuthenticator = app(Authenticator::class)->boot(request());

        // check() sees if the user is authenticated for the session via webauthn, but if the user is authenticated using TOTP they need to be able to add a key too
        return static::webauthnEnabled() && (! static::enabled($user) || ($totpAuthenticator->isAuthenticated() || static::check()));
    }
}
