<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Email;

class DisallowedReplySendAttempt extends Notification implements ShouldQueue, ShouldBeEncrypted
{
    use Queueable;

    protected $aliasEmail;

    protected $recipient;

    protected $destination;

    protected $authenticationResults;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($alias, $recipient, $authenticationResults)
    {
        $this->aliasEmail = $alias['local_part'].'@'.$alias['domain'];
        $this->recipient = $recipient;
        $this->destination = Str::replaceLast('=', '@', $alias['extension']);
        $this->authenticationResults = $authenticationResults;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $fingerprint = $notifiable->should_encrypt ? $notifiable->fingerprint : null;

        return (new MailMessage())
            ->subject('Disallowed reply/send from alias')
            ->markdown('mail.disallowed_reply_send_attempt', [
                'aliasEmail' => $this->aliasEmail,
                'recipient' => $this->recipient,
                'destination' => $this->destination,
                'authenticationResults' => $this->authenticationResults,
                'recipientId' => $notifiable->id,
                'fingerprint' => $fingerprint,
            ])
            ->withSymfonyMessage(function (Email $message) {
                $message->getHeaders()
                    ->addTextHeader('Feedback-ID', 'DRSA:anonaddy');
            });
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
