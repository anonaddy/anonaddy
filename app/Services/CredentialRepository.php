<?php

namespace LaravelWebauthn\Services\Webauthn;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Support\Collection;
use LaravelWebauthn\Facades\Webauthn;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialSource;

class CredentialRepository
{
    /**
     * List of PublicKeyCredentialSource associated to the user.
     *
     * @return Collection<array-key,PublicKeyCredentialSource>
     */
    protected static function getAllRegisteredKeys(int|string $userId, bool $onlyEnabled = false): Collection
    {
        // Added override with enabled true
        return (Webauthn::model())::where('user_id', $userId)
            ->when($onlyEnabled, function ($query) {
                $query->where('enabled', true);
            })
            ->get()
            ->map
            ->publicKeyCredentialSource;
    }

    /**
     * List of registered PublicKeyCredentialDescriptor associated to the user.
     *
     * @return array<array-key,PublicKeyCredentialDescriptor>
     */
    public static function getRegisteredKeys(User $user): array
    {
        [$childClass, $calledBy] = debug_backtrace(false, 2);

        // If we are registering a new key then we want to get all the user's keys including disabled ones
        if ($calledBy['function'] === 'getExcludedCredentials') {
            return static::getAllRegisteredKeys($user->getAuthIdentifier())
                ->map
                ->getPublicKeyCredentialDescriptor()
                ->toArray();
        }

        // Else just get the enabled keys for getAllowedCredentials when authenticating
        return static::getAllRegisteredKeys($user->getAuthIdentifier(), true)
            ->map
            ->getPublicKeyCredentialDescriptor()
            ->toArray();
    }
}
