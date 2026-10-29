<?php

namespace Tests\Unit;

use App\Services\UserRuleChecker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserRuleCheckerWebhookTargetsTest extends TestCase
{
    #[Test]
    public function test_same_webhook_url_includes_the_body_when_any_rule_asks_for_it(): void
    {
        $targets = UserRuleChecker::webhookTargets([
            'one' => [[
                'type' => 'webhook',
                'value' => [
                    'url' => 'https://example.com/hooks/addy',
                    'include_body' => false,
                ],
            ]],
            'two' => [[
                'type' => 'webhook',
                'value' => [
                    'url' => 'https://example.com/hooks/addy',
                    'include_body' => true,
                    'include_headers' => true,
                ],
            ]],
            'three' => [[
                'type' => 'webhook',
                'value' => [
                    'url' => 'https://other.example.com/hook',
                    'include_body' => false,
                ],
            ]],
        ]);

        $this->assertSame([
            'https://example.com/hooks/addy' => [
                'include_body' => true,
                'include_headers' => true,
            ],
            'https://other.example.com/hook' => [
                'include_body' => false,
                'include_headers' => false,
            ],
        ], $targets);
    }
}
