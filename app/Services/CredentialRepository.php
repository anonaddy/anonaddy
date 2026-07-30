<?php

namespace LaravelWebauthn\Services\Webauthn;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Support\Collection;
use LaravelWebauthn\Facades\Webauthn;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredentialDescriptor;

class CredentialRepository
{
    /**
     * List of CredentialRecord associated to the user.
     *
     * @return Collection<array-key, CredentialRecord>
     */
    protected function getAllRegisteredKeys(int|string $userId, bool $onlyEnabled = false): Collection
    {
        // Override: filter by enabled when authenticating, include all when registering.
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
     * @return array<array-key, PublicKeyCredentialDescriptor>
     */
    public function getRegisteredKeys(User $user): array
    {
        [$childClass, $calledBy] = debug_backtrace(false, 2);

        // If we are registering a new key then we want to get all the user's keys including disabled ones
        if ($calledBy['function'] === 'getExcludedCredentials') {
            return $this->getAllRegisteredKeys($user->getAuthIdentifier())
                ->map
                ->getPublicKeyCredentialDescriptor()
                ->toArray();
        }

        // Else just get the enabled keys for getAllowedCredentials when authenticating
        return $this->getAllRegisteredKeys($user->getAuthIdentifier(), true)
            ->map
            ->getPublicKeyCredentialDescriptor()
            ->toArray();
    }
}
