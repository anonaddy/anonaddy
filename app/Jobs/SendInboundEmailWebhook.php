<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\InboundEmailWebhookPaused;
use App\Services\InboundEmailWebhookCircuit;
use App\Services\WebhookUrlGuard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendInboundEmailWebhook implements ShouldBeEncrypted, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 4;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10, 60, 300];

    public int $timeout = 20;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $userId,
        public string $url,
        public array $payload,
    ) {}

    public function handle(WebhookUrlGuard $guard, InboundEmailWebhookCircuit $circuit): void
    {
        $pin = $guard->connectionPin($this->url);

        if ($pin === null) {
            Log::warning('Inbound email webhook URL was rejected', [
                'user_id' => $this->userId,
                'host' => parse_url($this->url, PHP_URL_HOST),
            ]);

            $this->noteFailure($circuit);

            return;
        }

        $user = User::find($this->userId);
        $secret = $user?->webhook_secret;

        if (! is_string($secret) || $secret === '') {
            return;
        }

        $body = json_encode(
            $this->payload,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
        );

        $options = [
            'allow_redirects' => false,
        ];

        if (! $pin['literal']) {
            $options['curl'] = [
                CURLOPT_RESOLVE => [
                    $guard->curlResolveLine($pin['host'], $pin['port'], $pin['address']),
                ],
            ];
        }

        $response = Http::withBody($body, 'application/json')
            ->withHeaders([
                'X-Addy-Signature' => hash_hmac('sha256', $body, $secret),
                'X-Addy-Event' => 'alias.email.received',
            ])
            ->withOptions($options)
            ->connectTimeout(3)
            ->timeout(15)
            ->post($this->url);

        if ($response->successful()) {
            $circuit->recordSuccess($this->userId, $this->url);

            return;
        }

        if ($response->serverError() || $response->status() === 429) {
            $response->throw();
        }

        $this->noteFailure($circuit);
    }

    public function failed(?Throwable $exception): void
    {
        $this->noteFailure(app(InboundEmailWebhookCircuit::class));
    }

    protected function noteFailure(InboundEmailWebhookCircuit $circuit): void
    {
        if (! $circuit->recordFailure($this->userId, $this->url)) {
            return;
        }

        $user = User::find($this->userId);

        if ($user === null) {
            return;
        }

        $user->notify(new InboundEmailWebhookPaused($this->url));
    }
}
