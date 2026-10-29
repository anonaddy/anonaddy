<?php

namespace Tests\Unit;

use App\Services\InboundEmailWebhookPayload;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InboundEmailWebhookPayloadTest extends TestCase
{
    #[Test]
    public function test_keeps_text_and_html_that_fit_under_the_cap(): void
    {
        $capped = InboundEmailWebhookPayload::capBodies('hello', '<p>hello</p>');

        $this->assertFalse($capped['truncated']);
        $this->assertSame('hello', $capped['text']);
        $this->assertSame('<p>hello</p>', $capped['html']);
    }

    #[Test]
    public function test_caps_the_combined_body_at_one_megabyte(): void
    {
        $text = str_repeat('a', InboundEmailWebhookPayload::BODY_BYTE_CAP);
        $capped = InboundEmailWebhookPayload::capBodies($text, 'b');

        $this->assertTrue($capped['truncated']);
        $this->assertSame('', $capped['html']);
        $this->assertLessThanOrEqual(
            InboundEmailWebhookPayload::BODY_BYTE_CAP,
            strlen($capped['text']) + strlen($capped['html'])
        );
    }

    #[Test]
    public function test_keeps_useful_headers_and_drops_routing_headers(): void
    {
        $capped = InboundEmailWebhookPayload::capHeaders([
            'list-unsubscribe: <mailto:unsubscribe@example.com>',
            'received: from internal.addy.io',
            'dkim-signature: v=1; a=rsa-sha256',
            'x-anonaddy-spam: Yes',
            'x-spamd-result: default: False [-0.00 / 900.00]',
            'reply-to: billing@example.com',
        ]);

        $this->assertFalse($capped['headers_truncated']);
        $this->assertSame([
            ['name' => 'list-unsubscribe', 'value' => '<mailto:unsubscribe@example.com>'],
            ['name' => 'reply-to', 'value' => 'billing@example.com'],
        ], $capped['headers']);
    }

    #[Test]
    public function test_caps_headers_at_one_megabyte(): void
    {
        $capped = InboundEmailWebhookPayload::capHeaders([
            'subject: '.str_repeat('a', InboundEmailWebhookPayload::BODY_BYTE_CAP),
            'reply-to: billing@example.com',
        ]);

        $this->assertTrue($capped['headers_truncated']);
        $this->assertCount(1, $capped['headers']);
        $this->assertLessThanOrEqual(
            InboundEmailWebhookPayload::BODY_BYTE_CAP,
            strlen($capped['headers'][0]['name']) + strlen($capped['headers'][0]['value'])
        );
    }
}
