<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Symfony\Component\Mime\Email;

class NewRecipientVerified extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    protected $newRecipientEmail;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($newRecipientEmail)
    {
        $this->newRecipientEmail = $newRecipientEmail;
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
        return (new MailMessage)
            ->subject('A new recipient has just been verified on your account')
            ->markdown('mail.new_recipient_verified', [
                'newRecipient' => $this->newRecipientEmail,
                'userId' => $notifiable->user_id,
                'recipientId' => $notifiable->id,
                'emailType' => 'NRV',
                'fingerprint' => $notifiable->should_encrypt ? $notifiable->fingerprint : null,
            ])
            ->withSymfonyMessage(function (Email $message) {
                $message->getHeaders()
                    ->addTextHeader('Feedback-ID', 'NRV:anonaddy');
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
