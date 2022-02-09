<?php

namespace App\Services;

use App\Models\WebauthnKey;
use Illuminate\Contracts\Auth\Authenticatable as User;
use LaravelWebauthn\Services\Webauthn as ServicesWebauthn;
use Webauthn\PublicKeyCredentialSource;

class Webauthn extends ServicesWebauthn
{
    /**
     * Create a new key.
     *
     * @param  User  $user
     * @param  string  $keyName
     * @param  PublicKeyCredentialSource  $publicKeyCredentialSource
     * @return WebauthnKey
     */
    public function create(User $user, string $keyName, PublicKeyCredentialSource $publicKeyCredentialSource)
    {
        $webauthnKey = new WebauthnKey();
        $webauthnKey->user_id = $user->getAuthIdentifier();
        $webauthnKey->name = $keyName;
        $webauthnKey->publicKeyCredentialSource = $publicKeyCredentialSource;
        $webauthnKey->save();

        return $webauthnKey;
    }

    /**
     * Test if the user has one or more webauthn key.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return bool
     */
    public function enabled(User $user): bool
    {
        return $this->webauthnEnabled() && $this->hasKey($user);
    }

    /**
     * Detect if user has a key that is enabled.
     *
     * @param User $user
     * @return bool
     */
    public function hasKey(User $user): bool
    {
        return WebauthnKey::where('user_id', $user->getAuthIdentifier())->where('enabled', true)->count() > 0;
    }
}
