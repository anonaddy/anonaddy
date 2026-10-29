<?php

namespace Tests\Unit;

use App\Services\WebhookUrlGuard;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class WebhookUrlGuardTest extends TestCase
{
    #[Test]
    #[TestWith(['http://example.com/hook'])]
    #[TestWith(['https://user:pass@example.com/hook'])]
    #[TestWith(['https://127.0.0.1/hook'])]
    #[TestWith(['https://10.1.1.1/hook'])]
    #[TestWith(['https://192.168.0.5/hook'])]
    #[TestWith(['https://169.254.169.254/latest'])]
    #[TestWith(['https://[::1]/hook'])]
    #[TestWith(['https://localhost/hook'])]
    #[TestWith(['https://example.com/hook#fragment'])]
    #[TestWith(['https://100.100.100.200/latest'])]
    #[TestWith(['https://100.64.0.1/hook'])]
    #[TestWith(['https://198.18.0.1/hook'])]
    #[TestWith(['https://192.0.0.8/hook'])]
    #[TestWith(['https://[64:ff9b::a00:1]/hook'])]
    #[TestWith(['https://[2002:a00:1::1]/hook'])]
    #[TestWith(['https://[2001:0:4136:e378::1]/hook'])]
    #[TestWith(['https://[fec0::1]/hook'])]
    public function test_rejects_an_unsafe_webhook_url(string $url): void
    {
        $this->assertSame(
            WebhookUrlGuard::PUBLIC_HTTPS_MESSAGE,
            (new WebhookUrlGuard)->failureReason($url)
        );
    }

    #[Test]
    #[TestWith(['https://1.1.1.1/hook'])]
    #[TestWith(['https://100.128.0.1/hook'])]
    #[TestWith(['https://[2606:4700:4700::1111]/hook'])]
    public function test_allows_a_public_ip_address(string $url): void
    {
        $this->assertNull((new WebhookUrlGuard)->failureReason($url));
    }

    #[Test]
    public function test_rejects_a_hostname_that_resolves_to_a_private_address(): void
    {
        $guard = \Mockery::mock(WebhookUrlGuard::class)->makePartial();
        $guard->shouldReceive('resolveHost')->once()->with('hooks.test')->andReturn(['10.0.0.8']);

        $this->assertSame(WebhookUrlGuard::PUBLIC_HTTPS_MESSAGE, $guard->failureReason('https://hooks.test/in'));
    }

    #[Test]
    public function test_rejects_a_hostname_that_does_not_resolve(): void
    {
        $guard = \Mockery::mock(WebhookUrlGuard::class)->makePartial();
        $guard->shouldReceive('resolveHost')->once()->with('hooks.test')->andReturn([]);

        $this->assertSame(WebhookUrlGuard::UNRESOLVED_HOST_MESSAGE, $guard->failureReason('https://hooks.test/in'));
    }

    #[Test]
    public function test_queries_the_host_as_an_absolute_name(): void
    {
        $guard = new class extends WebhookUrlGuard
        {
            public function queryName(string $host): string
            {
                return $this->absoluteDnsName($host);
            }
        };

        $this->assertSame('example-does-not-exist243564573567.com.', $guard->queryName('example-does-not-exist243564573567.com'));
        $this->assertSame('example.com.', $guard->queryName('example.com.'));
    }

    #[Test]
    public function test_allows_a_hostname_that_resolves_to_a_public_address(): void
    {
        $guard = \Mockery::mock(WebhookUrlGuard::class)->makePartial();
        $guard->shouldReceive('resolveHost')->once()->with('hooks.test')->andReturn(['1.1.1.1']);

        $this->assertNull($guard->failureReason('https://hooks.test/in'));
    }

    #[Test]
    public function test_pins_the_connection_to_a_public_address_from_the_same_lookup(): void
    {
        $guard = \Mockery::mock(WebhookUrlGuard::class)->makePartial();
        $guard->shouldReceive('resolveHost')->once()->with('hooks.test')->andReturn([
            '2606:4700:4700::1111',
            '1.1.1.1',
        ]);

        $this->assertSame([
            'host' => 'hooks.test',
            'port' => 8443,
            'address' => '1.1.1.1',
            'literal' => false,
        ], $guard->connectionPin('https://hooks.test:8443/in'));

        $this->assertSame(
            'hooks.test:443:[2606:4700:4700::1111]',
            $guard->curlResolveLine('hooks.test', 443, '2606:4700:4700::1111')
        );
    }

    #[Test]
    public function test_pins_a_literal_public_address_without_a_lookup(): void
    {
        $guard = \Mockery::mock(WebhookUrlGuard::class)->makePartial();
        $guard->shouldReceive('resolveHost')->never();

        $this->assertSame([
            'host' => '1.1.1.1',
            'port' => 443,
            'address' => '1.1.1.1',
            'literal' => true,
        ], $guard->connectionPin('https://1.1.1.1/hook'));
    }
}
