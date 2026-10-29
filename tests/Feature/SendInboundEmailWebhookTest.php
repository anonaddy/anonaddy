<?php

namespace Tests\Feature;

use App\Jobs\SendInboundEmailWebhook;
use App\Notifications\InboundEmailWebhookPaused;
use App\Services\InboundEmailWebhookCircuit;
use App\Services\WebhookUrlGuard;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendInboundEmailWebhookTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function test_posts_a_signed_json_body(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://example.com/hooks/addy' => Http::response('ok', 200),
        ]);

        $this->mock(WebhookUrlGuard::class, function ($mock) {
            $mock->shouldReceive('connectionPin')->once()->with('https://example.com/hooks/addy')->andReturn([
                'host' => 'example.com',
                'port' => 443,
                'address' => '1.1.1.1',
                'literal' => false,
            ]);
            $mock->shouldReceive('curlResolveLine')->once()->with('example.com', 443, '1.1.1.1')->andReturn('example.com:443:1.1.1.1');
        });

        $user = $this->createUser();
        $user->webhook_secret = 'secret-value';
        $user->save();

        $payload = [
            'event' => 'alias.email.received',
            'from' => 'will@anonaddy.com',
            'text' => 'Hello',
            'html' => '<p>Hello</p>',
            'truncated' => false,
        ];

        app()->call([new SendInboundEmailWebhook($user->id, 'https://example.com/hooks/addy', $payload), 'handle']);

        Http::assertSent(function (Request $request) {
            $body = $request->body();

            return $request->url() === 'https://example.com/hooks/addy'
                && $request->header('X-Addy-Signature')[0] === hash_hmac('sha256', $body, 'secret-value')
                && $request->header('X-Addy-Event')[0] === 'alias.email.received'
                && $request->data()['text'] === 'Hello'
                && $request->data()['html'] === '<p>Hello</p>';
        });
    }

    #[Test]
    public function test_does_not_post_when_the_url_is_a_private_address(): void
    {
        Http::preventStrayRequests();
        Http::fake();

        $user = $this->createUser();
        $user->webhook_secret = 'secret-value';
        $user->save();

        app()->call([new SendInboundEmailWebhook($user->id, 'https://127.0.0.1/hook', [
            'event' => 'alias.email.received',
        ]), 'handle']);

        Http::assertNothingSent();
    }

    #[Test]
    public function test_does_not_retry_a_client_error(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://example.com/hooks/addy' => Http::response('missing', 404),
        ]);

        $this->allowPinnedExampleHost();

        $user = $this->createUser();
        $user->webhook_secret = 'secret-value';
        $user->save();

        app()->call([new SendInboundEmailWebhook($user->id, 'https://example.com/hooks/addy', [
            'event' => 'alias.email.received',
        ]), 'handle']);

        Http::assertSentCount(1);
    }

    #[Test]
    public function test_fails_a_server_error_so_the_job_can_retry(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://example.com/hooks/addy' => Http::response('down', 500),
        ]);

        $this->allowPinnedExampleHost();

        $user = $this->createUser();
        $user->webhook_secret = 'secret-value';
        $user->save();

        $this->expectException(RequestException::class);

        app()->call([new SendInboundEmailWebhook($user->id, 'https://example.com/hooks/addy', [
            'event' => 'alias.email.received',
        ]), 'handle']);
    }

    #[Test]
    public function test_fifth_client_error_pauses_the_url_and_emails_once(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://example.com/hooks/addy' => Http::response('missing', 404),
        ]);
        Notification::fake();

        $this->allowPinnedExampleHost();

        $user = $this->createUser();
        $user->webhook_secret = 'secret-value';
        $user->save();
        $url = 'https://example.com/hooks/addy';

        foreach (range(1, InboundEmailWebhookCircuit::FAILURES_BEFORE_PAUSE - 1) as $ignored) {
            app()->call([new SendInboundEmailWebhook($user->id, $url, [
                'event' => 'alias.email.received',
            ]), 'handle']);
        }

        Notification::assertNothingSent();

        app()->call([new SendInboundEmailWebhook($user->id, $url, [
            'event' => 'alias.email.received',
        ]), 'handle']);

        Notification::assertSentToTimes($user, InboundEmailWebhookPaused::class, 1);
        $this->assertTrue(app(InboundEmailWebhookCircuit::class)->isPaused($user->id, $url));

        app()->call([new SendInboundEmailWebhook($user->id, $url, [
            'event' => 'alias.email.received',
        ]), 'handle']);

        Notification::assertSentToTimes($user, InboundEmailWebhookPaused::class, 1);
    }

    #[Test]
    public function test_successful_delivery_clears_the_pause(): void
    {
        Http::preventStrayRequests();
        Notification::fake();

        $this->allowPinnedExampleHost();

        $user = $this->createUser();
        $user->webhook_secret = 'secret-value';
        $user->save();
        $url = 'https://example.com/hooks/addy';
        $circuit = app(InboundEmailWebhookCircuit::class);

        foreach (range(1, InboundEmailWebhookCircuit::FAILURES_BEFORE_PAUSE) as $ignored) {
            $circuit->recordFailure($user->id, $url);
        }

        Http::fake([
            $url => Http::response('ok', 200),
        ]);

        app()->call([new SendInboundEmailWebhook($user->id, $url, [
            'event' => 'alias.email.received',
        ]), 'handle']);

        $this->assertFalse($circuit->isPaused($user->id, $url));

        Http::fake([
            $url => Http::response('missing', 404),
        ]);

        app()->call([new SendInboundEmailWebhook($user->id, $url, [
            'event' => 'alias.email.received',
        ]), 'handle']);

        Notification::assertNothingSent();
        $this->assertFalse($circuit->isPaused($user->id, $url));
    }

    #[Test]
    public function test_counts_a_server_error_only_after_the_job_fails(): void
    {
        Notification::fake();

        $user = $this->createUser();
        $url = 'https://example.com/hooks/addy';
        $job = new SendInboundEmailWebhook($user->id, $url, [
            'event' => 'alias.email.received',
        ]);

        foreach (range(1, InboundEmailWebhookCircuit::FAILURES_BEFORE_PAUSE - 1) as $ignored) {
            $job->failed(new ConnectionException('down'));
        }

        Notification::assertNothingSent();

        $job->failed(new ConnectionException('down'));

        Notification::assertSentToTimes($user, InboundEmailWebhookPaused::class, 1);
        $this->assertTrue(app(InboundEmailWebhookCircuit::class)->isPaused($user->id, $url));
    }

    #[Test]
    public function test_pause_email_names_the_url_and_the_rules_page(): void
    {
        $user = $this->createUser();
        $mail = (new InboundEmailWebhookPaused('https://hooks.example/in'))->toMail($user);

        $this->assertSame('Webhook paused after failed deliveries', $mail->subject);
        $html = $mail->render();
        $this->assertStringContainsString('https://hooks.example/in', $html);
        $this->assertStringContainsString('/rules', $html);
        $this->assertStringContainsString('1 hour', $html);
    }

    private function allowPinnedExampleHost(): void
    {
        $this->mock(WebhookUrlGuard::class, function ($mock) {
            $mock->shouldReceive('connectionPin')->andReturn([
                'host' => 'example.com',
                'port' => 443,
                'address' => '1.1.1.1',
                'literal' => false,
            ]);
            $mock->shouldReceive('curlResolveLine')->with('example.com', 443, '1.1.1.1')->andReturn('example.com:443:1.1.1.1');
        });
    }
}
