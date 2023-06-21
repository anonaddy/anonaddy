<?php

namespace App\Actions;

use App\Facades\Webauthn;
use App\Models\WebauthnKey;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use LaravelWebauthn\Actions\RegisterKeyStore as ActionsRegisterKeyStore;
use LaravelWebauthn\Events\WebauthnRegister;
use LaravelWebauthn\Services\Webauthn\CredentialAttestationValidator;
use Webauthn\PublicKeyCredentialCreationOptions;

class RegisterKeyStore extends ActionsRegisterKeyStore
{
    /**
     * Register a new key.
     */
    public function __invoke(Authenticatable $user, PublicKeyCredentialCreationOptions $publicKey, string $data, string $keyName): ?WebauthnKey
    {
        if (! Webauthn::canRegister($user)) {
            $this->throwFailedRegisterException($user);
        }

        try {
            $publicKeyCredentialSource = $this->app[CredentialAttestationValidator::class]($publicKey, $data);

            $webauthnKey = Webauthn::create($user, $keyName, $publicKeyCredentialSource);

            WebauthnRegister::dispatch($webauthnKey);

            Webauthn::login();

            return $webauthnKey;
        } catch (Exception $e) {
            $this->throwFailedRegisterException($user, $e);
        }

        return null;
    }
}
