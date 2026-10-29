<?php

namespace App\Notifications;

use App\Services\InboundEmailWebhookCircuit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Symfony\Component\Mime\Email;

class InboundEmailWebhookPaused extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public function __construct(protected string $url) {}

    /**
     * @param  mixed  $notifiable
     * @return list<string>
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $recipient = $notifiable->defaultRecipient;
        $fingerprint = $recipient->should_encrypt ? $recipient->fingerprint : null;

        return (new MailMessage)
            ->subject('Webhook paused after failed deliveries')
            ->markdown('mail.inbound_email_webhook_paused', [
                'url' => $this->url,
                'failureCount' => InboundEmailWebhookCircuit::FAILURES_BEFORE_PAUSE,
                'pauseFor' => '1 hour',
                'userId' => $notifiable->id,
                'recipientId' => $notifiable->default_recipient_id,
                'emailType' => 'WHP',
                'hasVerifiedEmail' => $recipient->hasVerifiedEmail(),
                'fingerprint' => $fingerprint,
            ])
            ->withSymfonyMessage(function (Email $message) {
                $message->getHeaders()
                    ->addTextHeader('Feedback-ID', 'WHP:anonaddy');
            });
    }

    /**
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [];
    }
}
