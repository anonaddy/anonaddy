<?php

namespace App\Services;

use App\Jobs\SendInboundEmailWebhook;
use App\Models\Alias;
use App\Models\EmailData;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class InboundEmailWebhookDispatcher
{
    public function __construct(private InboundEmailWebhookCircuit $circuit) {}

    public function queue(User $user, Alias $alias, EmailData $emailData, array $ruleIdsAndActions, string $emailType): void
    {
        if (UserRuleChecker::shouldBlockEmail($ruleIdsAndActions) || UserRuleChecker::shouldQuarantineEmail($ruleIdsAndActions)) {
            return;
        }

        $targets = UserRuleChecker::webhookTargets($ruleIdsAndActions);

        if ($targets === []) {
            return;
        }

        $user->ensureWebhookSecret();

        foreach ($targets as $url => $options) {
            if ($this->circuit->isPaused($user->id, $url)) {
                Log::info('Inbound email webhook is paused', [
                    'user_id' => $user->id,
                    'host' => parse_url($url, PHP_URL_HOST),
                ]);

                continue;
            }

            SendInboundEmailWebhook::dispatch(
                $user->id,
                $url,
                InboundEmailWebhookPayload::make($alias, $emailData, $emailType, $options['include_body'], $options['include_headers']),
            );
        }
    }
}
