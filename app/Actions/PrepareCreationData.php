<?php

namespace App\Actions;

use App\Facades\Webauthn;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Validation\ValidationException;
use LaravelWebauthn\Events\WebauthnRegisterFailed;
use LaravelWebauthn\Services\Webauthn\PublicKeyCredentialCreationOptions;

class PrepareCreationData
{
    /**
     * Get data to register a new key.
     */
    public function __invoke(User $user): PublicKeyCredentialCreationOptions
    {
        if (! Webauthn::canRegister($user)) {
            $this->throwFailedRegisterException($user);
        }

        return Webauthn::prepareAttestation($user);
    }

    /**
     * Throw a failed register validation exception.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function throwFailedRegisterException(User $user, ?Exception $e = null): void
    {
        WebauthnRegisterFailed::dispatch($user, $e);

        throw ValidationException::withMessages([
            Webauthn::username() => [trans('webauthn::errors.cannot_register_new_key')],
        ]);
    }
}
