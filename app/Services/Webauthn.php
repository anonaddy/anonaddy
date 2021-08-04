<?php

namespace App\Services;

use App\Models\WebauthnKey;
use Illuminate\Contracts\Auth\Authenticatable as User;
use LaravelWebauthn\Events\WebauthnRegister;
use LaravelWebauthn\Services\Webauthn as ServicesWebauthn;
use LaravelWebauthn\Services\Webauthn\PublicKeyCredentialValidator;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialSource;

class Webauthn extends ServicesWebauthn
{
    public function doRegister(User $user, PublicKeyCredentialCreationOptions $publicKey, string $data, string $keyName): WebauthnKey
    {
        $publicKeyCredentialSource = $this->app->make(PublicKeyCredentialValidator::class)
            ->validate($publicKey, $data);

        $webauthnKey = $this->create($user, $keyName, $publicKeyCredentialSource);

        $this->forceAuthenticate();

        $this->events->dispatch(new WebauthnRegister($webauthnKey));

        return $webauthnKey;
    }

    /**
     * Create a new key.
     *
     * @param User $user
     * @param string $keyName
     * @param PublicKeyCredentialSource $publicKeyCredentialSource
     * @return WebauthnKey
     */
    public function create(User $user, string $keyName, PublicKeyCredentialSource $publicKeyCredentialSource)
    {
        $webauthnKey = WebauthnKey::make([
            'user_id' => $user->getAuthIdentifier(),
            'name' => $keyName,
        ]);

        $webauthnKey->publicKeyCredentialSource = $publicKeyCredentialSource;
        $webauthnKey->save();

        return $webauthnKey;
    }

    /**
     * Test if the user has one webauthn key set or more.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return bool
     */
    public function enabled(User $user): bool
    {
        return (bool) $this->config->get('webauthn.enable', true) && $this->hasKey($user);
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
