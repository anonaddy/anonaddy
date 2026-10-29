<?php

namespace Tests\Unit;

use App\Services\InboundEmailWebhookCircuit;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InboundEmailWebhookCircuitTest extends TestCase
{
    #[Test]
    public function test_pause_ends_after_one_hour_without_a_second_notice(): void
    {
        $this->freezeTime();

        $circuit = new InboundEmailWebhookCircuit;
        $userId = 'user-1';
        $url = 'https://hooks.example/in';
        $startedPause = false;

        foreach (range(1, InboundEmailWebhookCircuit::FAILURES_BEFORE_PAUSE) as $ignored) {
            $startedPause = $circuit->recordFailure($userId, $url);
        }

        $this->assertTrue($startedPause);
        $this->assertTrue($circuit->isPaused($userId, $url));

        $this->travel(InboundEmailWebhookCircuit::PAUSE_SECONDS)->seconds();

        $this->assertFalse($circuit->isPaused($userId, $url));
        $this->assertFalse($circuit->recordFailure($userId, $url));
        $this->assertTrue($circuit->isPaused($userId, $url));
    }

    #[Test]
    public function test_success_clears_the_failure_count(): void
    {
        $circuit = new InboundEmailWebhookCircuit;
        $userId = 'user-2';
        $url = 'https://hooks.example/cleared';

        foreach (range(1, InboundEmailWebhookCircuit::FAILURES_BEFORE_PAUSE) as $ignored) {
            $circuit->recordFailure($userId, $url);
        }

        $circuit->recordSuccess($userId, $url);

        $this->assertFalse($circuit->isPaused($userId, $url));
        $this->assertFalse($circuit->recordFailure($userId, $url));
    }
}
