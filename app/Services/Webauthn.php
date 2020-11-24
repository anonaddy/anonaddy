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
}
