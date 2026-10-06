<?php

namespace Tests\Unit;

use App\Dns\DomainDnsLookup;
use App\Models\Domain;
use Exception;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DomainSendingVerificationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['anonaddy.run_dns_checks' => true]);
    }

    #[Test]
    public function it_returns_dns_error_without_marking_an_unverified_domain_as_verified_when_lookups_fail(): void
    {
        $user = $this->createUser();
        $domain = Domain::factory()->create([
            'user_id' => $user->id,
            'domain' => 'example.com',
        ]);

        $this->mock(DomainDnsLookup::class, function ($mock) {
            $mock->shouldReceive('getRecords')
                ->andThrow(new Exception('dns_get_record timed out'));
        });

        $result = $domain->checkVerificationForSending()->getData();

        $this->assertFalse($result->success);
        $this->assertTrue($result->dns_error);
        $this->assertNull($domain->refresh()->domain_sending_verified_at);
    }

    #[Test]
    public function it_returns_dns_error_when_dns_get_record_returns_false(): void
    {
        $user = $this->createUser();
        $domain = Domain::factory()->create([
            'user_id' => $user->id,
            'domain' => 'example.com',
            'domain_sending_verified_at' => now(),
        ]);

        $this->mock(DomainDnsLookup::class, function ($mock) {
            $mock->shouldReceive('getRecords')->andReturn(false);
        });

        $result = $domain->checkVerificationForSending()->getData();

        $this->assertTrue($result->success);
        $this->assertTrue($result->dns_error);
        $this->assertNotNull($domain->refresh()->domain_sending_verified_at);
    }
}
