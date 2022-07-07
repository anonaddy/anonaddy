<?php

namespace App\Services;

use App\Models\WebauthnKey;
use Illuminate\Contracts\Auth\Authenticatable as User;
use LaravelWebauthn\Services\Webauthn as ServicesWebauthn;

class Webauthn extends ServicesWebauthn
{
    /**
     * Test if the user has one or more webauthn key.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return bool
     */
    public static function enabled(User $user): bool
    {
        return static::webauthnEnabled() && static::hasKey($user);
    }

    /**
     * Detect if user has a key that is enabled.
     *
     * @param User $user
     * @return bool
     */
    public static function hasKey(User $user): bool
    {
        return WebauthnKey::where('user_id', $user->getAuthIdentifier())->where('enabled', true)->count() > 0;
    }
}
