<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class InboundEmailWebhookCircuit
{
    public const FAILURES_BEFORE_PAUSE = 5;

    public const PAUSE_SECONDS = 3600;

    public const STATE_TTL_SECONDS = 604800;

    public function isPaused(string $userId, string $url): bool
    {
        $pausedUntil = $this->state($userId, $url)['paused_until'];

        return $pausedUntil !== null && $pausedUntil > now()->getTimestamp();
    }

    /**
     * Record one finished delivery that did not succeed.
     *
     * Returns true only when this failure starts the pause and the account has not already been told.
     */
    public function recordFailure(string $userId, string $url): bool
    {
        $startedPause = false;

        Cache::lock($this->lockKey($userId, $url), 10)->block(5, function () use ($userId, $url, &$startedPause) {
            $state = $this->state($userId, $url);
            $state['failures']++;

            if ($state['failures'] >= self::FAILURES_BEFORE_PAUSE) {
                $state['paused_until'] = now()->addSeconds(self::PAUSE_SECONDS)->getTimestamp();

                if (! $state['notified']) {
                    $state['notified'] = true;
                    $startedPause = true;
                }
            }

            Cache::put($this->cacheKey($userId, $url), $state, self::STATE_TTL_SECONDS);
        });

        return $startedPause;
    }

    public function recordSuccess(string $userId, string $url): void
    {
        Cache::lock($this->lockKey($userId, $url), 10)->block(5, function () use ($userId, $url) {
            Cache::forget($this->cacheKey($userId, $url));
        });
    }

    /**
     * @return array{failures: int, paused_until: int|null, notified: bool}
     */
    protected function state(string $userId, string $url): array
    {
        $state = Cache::get($this->cacheKey($userId, $url));

        if (! is_array($state)) {
            return [
                'failures' => 0,
                'paused_until' => null,
                'notified' => false,
            ];
        }

        return [
            'failures' => (int) ($state['failures'] ?? 0),
            'paused_until' => isset($state['paused_until']) ? (int) $state['paused_until'] : null,
            'notified' => (bool) ($state['notified'] ?? false),
        ];
    }

    protected function cacheKey(string $userId, string $url): string
    {
        return 'inbound-email-webhook:'.hash('sha256', $userId.'|'.$url);
    }

    protected function lockKey(string $userId, string $url): string
    {
        return $this->cacheKey($userId, $url).':lock';
    }
}
