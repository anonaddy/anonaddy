<?php

namespace Tests\Unit;

use LaravelWebauthn\Services\Webauthn\CredentialAttestationValidator;
use LaravelWebauthn\Services\Webauthn\CredentialRepository;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CredentialAttestationValidatorResolvesFromAppTest extends TestCase
{
    #[Test]
    public function credential_attestation_validator_is_loaded_from_vendor(): void
    {
        $filename = (new \ReflectionClass(CredentialAttestationValidator::class))->getFileName();

        $this->assertIsString($filename);
        $this->assertStringContainsString('vendor/asbiin/laravel-webauthn/', $filename);
    }

    #[Test]
    public function credential_repository_is_loaded_from_app_services(): void
    {
        $filename = (new \ReflectionClass(CredentialRepository::class))->getFileName();

        $this->assertIsString($filename);
        $this->assertStringEndsWith('app/Services/CredentialRepository.php', $filename);
    }
}
