<?php

namespace Tests\Unit;

use App\Dns\DomainDnsLookup;
use App\Models\Domain;
use App\Notifications\DomainUnverifiedForSending;
use Exception;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CheckDomainsSendingVerificationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'anonaddy.run_dns_checks' => true,
            'anonaddy.domain' => 'anonaddy.com',
            'anonaddy.dkim_selector' => 'default',
        ]);
    }

    #[Test]
    public function it_does_not_unverify_or_notify_when_dns_lookups_time_out(): void
    {
        Notification::fake();

        $user = $this->createUser();
        $domain = $this->createVerifiedSendingDomain($user->id, 'example.com');

        $this->mock(DomainDnsLookup::class, function ($mock) {
            $mock->shouldReceive('getRecords')
                ->andThrow(new Exception('dns_get_record timed out'));
        });

        $this->artisan('anonaddy:check-domains-sending-verification')
            ->assertSuccessful();

        $this->assertNotNull($domain->refresh()->domain_sending_verified_at);
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_unverifies_and_notifies_when_the_sending_records_are_missing(): void
    {
        Notification::fake();

        $user = $this->createUser();
        $domain = $this->createVerifiedSendingDomain($user->id, 'example.com');

        $this->mock(DomainDnsLookup::class, function ($mock) {
            $mock->shouldReceive('getRecords')->andReturn([]);
        });

        $this->artisan('anonaddy:check-domains-sending-verification')
            ->assertSuccessful();

        $this->assertNull($domain->refresh()->domain_sending_verified_at);
        Notification::assertSentTo($user, DomainUnverifiedForSending::class);
    }

    #[Test]
    public function it_leaves_the_domain_verified_when_sending_records_are_present(): void
    {
        Notification::fake();

        $user = $this->createUser();
        $domain = $this->createVerifiedSendingDomain($user->id, 'example.com');

        $this->mock(DomainDnsLookup::class, function ($mock) {
            $mock->shouldReceive('getRecords')
                ->andReturnUsing(function (string $name, int $type): array {
                    if ($type === DNS_TXT && ! str_starts_with($name, '_dmarc')) {
                        return [[
                            'txt' => 'v=spf1 include:spf.anonaddy.com -all',
                        ]];
                    }

                    if (str_starts_with($name, '_dmarc')) {
                        return [[
                            'txt' => 'v=DMARC1; p=reject;',
                        ]];
                    }

                    return [[
                        'target' => 'default._domainkey.anonaddy.com',
                    ]];
                });
        });

        $this->artisan('anonaddy:check-domains-sending-verification')
            ->assertSuccessful();

        $this->assertNotNull($domain->refresh()->domain_sending_verified_at);
        Notification::assertNothingSent();
    }

    protected function createVerifiedSendingDomain(string $userId, string $domain): Domain
    {
        return Domain::factory()->create([
            'user_id' => $userId,
            'domain' => $domain,
            'domain_sending_verified_at' => now(),
        ]);
    }
}
