<?php

namespace Tests\Unit;

use App\Mail\ForwardBannerAddress;
use App\Models\Alias;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ForwardBannerAddressTest extends TestCase
{
    #[Test]
    public function smtp_recipient_prefers_full_plus_address_detail(): void
    {
        $alias = Alias::factory()->make([
            'email' => 'shop@example.test',
            'local_part' => 'shop',
            'domain' => 'example.test',
        ]);

        $result = ForwardBannerAddress::forBanner('shop+eBay@Example.TEST', null, $alias);

        $this->assertSame('shop+ebay@example.test', $result);
    }

    #[Test]
    public function falls_back_to_original_to_header_when_smtp_unknown(): void
    {
        $alias = Alias::factory()->make([
            'email' => 'shop@example.test',
            'local_part' => 'shop',
            'domain' => 'example.test',
        ]);

        $result = ForwardBannerAddress::forBanner(null, 'Shop Receipts <shop+ebay@Example.TEST>', $alias);

        $this->assertSame('shop+ebay@example.test', $result);
    }

    #[Test]
    public function falls_back_to_stored_alias_email(): void
    {
        $alias = Alias::factory()->make([
            'email' => 'shop@example.test',
            'local_part' => 'shop',
            'domain' => 'example.test',
        ]);

        $result = ForwardBannerAddress::forBanner(null, null, $alias);

        $this->assertSame('shop@example.test', $result);
    }
}
