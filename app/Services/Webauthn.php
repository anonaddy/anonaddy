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
        if (! static::webauthnEnabled()) {
            return false;
        }

        // A user with no 2FA method yet can add their first key
        if (! $user->hasAnyTwoFactorEnabled() && ! static::enabled($user)) {
            return true;
        }

        // Otherwise 2FA must already have been passed for this session, via TOTP or webauthn
        $totpAuthenticated = $user->hasTotpEnabled() && app(Authenticator::class)->boot(request())->isAuthenticated();

        return $totpAuthenticated || static::check();
    }
}
