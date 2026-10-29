<?php

namespace Tests\Feature;

use App\Jobs\SendInboundEmailWebhook;
use App\Mail\ForwardEmail;
use App\Models\Alias;
use App\Models\EmailData;
use App\Models\Recipient;
use App\Models\Rule;
use App\Services\InboundEmailWebhookCircuit;
use App\Services\InboundEmailWebhookDispatcher;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use PhpMimeMailParser\Parser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ReceiveEmailWebhookTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function test_queues_one_webhook_for_an_accepted_forward_with_the_message_body(): void
    {
        Mail::fake();
        Queue::fake([SendInboundEmailWebhook::class]);

        $user = $this->createUser('johndoe');
        $domain = 'johndoe.'.config('anonaddy.domain');
        $alias = Alias::factory()->create([
            'user_id' => $user->id,
            'local_part' => 'ebay',
            'domain' => $domain,
            'email' => 'ebay@'.$domain,
        ]);
        $alias->recipients()->sync([
            Recipient::factory()->create(['user_id' => $user->id, 'email' => 'one@example.com'])->id,
            Recipient::factory()->create(['user_id' => $user->id, 'email' => 'two@example.com'])->id,
        ]);

        Rule::factory()->create([
            'user_id' => $user->id,
            'forwards' => true,
            'replies' => false,
            'sends' => false,
            'active' => true,
            'conditions' => [[
                'type' => 'sender',
                'match' => 'is exactly',
                'values' => ['will@anonaddy.com'],
            ]],
            'actions' => [[
                'type' => 'webhook',
                'value' => [
                    'url' => 'https://example.com/hooks/addy',
                    'include_body' => true,
                ],
            ]],
        ]);

        $this->artisan('anonaddy:receive-email', [
            'file' => base_path('tests/emails/email.eml'),
            '--sender' => 'will@anonaddy.com',
            '--recipient' => [$alias->email],
            '--local_part' => ['ebay'],
            '--extension' => [''],
            '--domain' => [$domain],
            '--size' => '1000',
        ])->assertExitCode(0);

        Mail::assertQueued(ForwardEmail::class, 2);
        Queue::assertPushed(SendInboundEmailWebhook::class, 1);
        Queue::assertPushed(SendInboundEmailWebhook::class, function (SendInboundEmailWebhook $job) use ($alias) {
            return $job->url === 'https://example.com/hooks/addy'
                && $job->payload['email_type'] === 'forward'
                && $job->payload['alias']['id'] === $alias->id
                && $job->payload['from'] === 'will@anonaddy.com'
                && $job->payload['subject'] === 'Test Email'
                && str_contains($job->payload['text'], 'This is a test email.')
                && str_contains($job->payload['html'], 'This is a test email.')
                && $job->payload['truncated'] === false
                && $job->payload['attachment_count'] === 0
                && ! array_key_exists('headers', $job->payload);
        });
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $user->fresh()->webhook_secret);
    }

    #[Test]
    public function test_omits_the_message_body_when_include_body_is_false(): void
    {
        Mail::fake();
        Queue::fake([SendInboundEmailWebhook::class]);

        $user = $this->createUser('johndoe');
        $domain = 'johndoe.'.config('anonaddy.domain');

        Rule::factory()->create([
            'user_id' => $user->id,
            'forwards' => true,
            'active' => true,
            'conditions' => [[
                'type' => 'sender',
                'match' => 'is exactly',
                'values' => ['will@anonaddy.com'],
            ]],
            'actions' => [[
                'type' => 'webhook',
                'value' => [
                    'url' => 'https://example.com/hooks/addy',
                    'include_body' => false,
                ],
            ]],
        ]);

        $this->artisan('anonaddy:receive-email', [
            'file' => base_path('tests/emails/email.eml'),
            '--sender' => 'will@anonaddy.com',
            '--recipient' => ['ebay@'.$domain],
            '--local_part' => ['ebay'],
            '--extension' => [''],
            '--domain' => [$domain],
            '--size' => '1000',
        ])->assertExitCode(0);

        Queue::assertPushed(SendInboundEmailWebhook::class, function (SendInboundEmailWebhook $job) {
            return ! array_key_exists('text', $job->payload)
                && ! array_key_exists('html', $job->payload)
                && ! array_key_exists('truncated', $job->payload)
                && ! array_key_exists('headers', $job->payload);
        });
    }

    #[Test]
    public function test_includes_selected_headers_when_include_headers_is_true(): void
    {
        Mail::fake();
        Queue::fake([SendInboundEmailWebhook::class]);

        $user = $this->createUser('johndoe');
        $domain = 'johndoe.'.config('anonaddy.domain');

        Rule::factory()->create([
            'user_id' => $user->id,
            'forwards' => true,
            'active' => true,
            'conditions' => [[
                'type' => 'sender',
                'match' => 'is exactly',
                'values' => ['will@anonaddy.com'],
            ]],
            'actions' => [[
                'type' => 'webhook',
                'value' => [
                    'url' => 'https://example.com/hooks/addy',
                    'include_body' => false,
                    'include_headers' => true,
                ],
            ]],
        ]);

        $this->artisan('anonaddy:receive-email', [
            'file' => base_path('tests/emails/email.eml'),
            '--sender' => 'will@anonaddy.com',
            '--recipient' => ['ebay@'.$domain],
            '--local_part' => ['ebay'],
            '--extension' => [''],
            '--domain' => [$domain],
            '--size' => '1000',
        ])->assertExitCode(0);

        Queue::assertPushed(SendInboundEmailWebhook::class, function (SendInboundEmailWebhook $job) {
            $names = array_column($job->payload['headers'], 'name');

            return in_array('list-unsubscribe', $names, true)
                && ! in_array('received', $names, true)
                && ! in_array('dkim-signature', $names, true)
                && ! in_array('x-spamd-result', $names, true)
                && $names === array_filter($names, fn (string $name) => ! str_starts_with($name, 'x-anonaddy-'))
                && $job->payload['headers_truncated'] === false
                && ! array_key_exists('text', $job->payload);
        });
    }

    #[Test]
    #[TestWith(['block'])]
    #[TestWith(['quarantine'])]
    public function test_blocked_or_quarantined_mail_does_not_queue_a_webhook(string $type): void
    {
        Queue::fake();

        $user = $this->createUser('johndoe');
        $domain = 'johndoe.'.config('anonaddy.domain');
        $alias = Alias::factory()->create([
            'user_id' => $user->id,
            'local_part' => 'ebay',
            'domain' => $domain,
            'email' => 'ebay@'.$domain,
        ]);

        $parser = new Parser;
        $parser->setPath(base_path('tests/emails/email.eml'));

        app(InboundEmailWebhookDispatcher::class)->queue(
            $user,
            $alias,
            new EmailData($parser, 'will@anonaddy.com', 1000),
            [
                'rule' => [
                    ['type' => $type, 'value' => true],
                    ['type' => 'webhook', 'value' => [
                        'url' => 'https://example.com/hooks/addy',
                        'include_body' => true,
                    ]],
                ],
            ],
            'F',
        );

        Queue::assertNotPushed(SendInboundEmailWebhook::class);
    }

    #[Test]
    public function test_does_not_queue_a_paused_webhook_url(): void
    {
        Queue::fake([SendInboundEmailWebhook::class]);

        $user = $this->createUser('johndoe');
        $domain = 'johndoe.'.config('anonaddy.domain');
        $alias = Alias::factory()->create([
            'user_id' => $user->id,
            'local_part' => 'ebay',
            'domain' => $domain,
            'email' => 'ebay@'.$domain,
        ]);
        $pausedUrl = 'https://paused.example/hook';
        $activeUrl = 'https://ok.example/hook';
        $circuit = app(InboundEmailWebhookCircuit::class);

        foreach (range(1, InboundEmailWebhookCircuit::FAILURES_BEFORE_PAUSE) as $ignored) {
            $circuit->recordFailure($user->id, $pausedUrl);
        }

        $parser = new Parser;
        $parser->setPath(base_path('tests/emails/email.eml'));

        app(InboundEmailWebhookDispatcher::class)->queue(
            $user,
            $alias,
            new EmailData($parser, 'will@anonaddy.com', 1000),
            [
                'paused' => [[
                    'type' => 'webhook',
                    'value' => ['url' => $pausedUrl, 'include_body' => false],
                ]],
                'active' => [[
                    'type' => 'webhook',
                    'value' => ['url' => $activeUrl, 'include_body' => false],
                ]],
            ],
            'F',
        );

        Queue::assertNotPushed(SendInboundEmailWebhook::class, function (SendInboundEmailWebhook $job) use ($pausedUrl) {
            return $job->url === $pausedUrl;
        });
        Queue::assertPushed(SendInboundEmailWebhook::class, function (SendInboundEmailWebhook $job) use ($activeUrl) {
            return $job->url === $activeUrl;
        });
    }
}
