<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Symfony\Component\Mime\Email;

class DefaultRecipientUpdated extends Notification implements ShouldQueue, ShouldBeEncrypted
{
    use Queueable;

    protected $newDefaultRecipient;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($newDefaultRecipient)
    {
        $this->newDefaultRecipient = $newDefaultRecipient;
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
        return (new MailMessage())
            ->subject('Your default recipient has just been updated')
            ->markdown('mail.default_recipient_updated', [
                'defaultRecipient' => $notifiable->email,
                'newDefaultRecipient' => $this->newDefaultRecipient,
                'recipientId' => $notifiable->id,
                'fingerprint' => $notifiable->should_encrypt ? $notifiable->fingerprint : null,
            ])
            ->withSymfonyMessage(function (Email $message) {
                $message->getHeaders()
                    ->addTextHeader('Feedback-ID', 'DRU:anonaddy');
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
