<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FailedDeliveryNotification extends Notification implements ShouldQueue, ShouldBeEncrypted
{
    use Queueable;

    protected $aliasEmail;

    protected $recipientEmail;

    protected $originalSender;

    protected $originalSubject;

    protected $isStored;

    protected $storeFailedDeliveries;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($aliasEmail, $originalSender, $originalSubject, $isStored = false, $storeFailedDeliveries = false, $recipientEmail = null)
    {
        $this->aliasEmail = $aliasEmail;
        $this->recipientEmail = $recipientEmail;
        $this->originalSender = $originalSender;
        $this->originalSubject = $originalSubject;
        $this->isStored = $isStored;
        $this->storeFailedDeliveries = $storeFailedDeliveries;
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
            ->subject('New failed delivery on AnonAddy')
            ->markdown('mail.failed_delivery_notification', [
                'aliasEmail' => $this->aliasEmail,
                'recipientEmail' => $this->recipientEmail ?? $notifiable->email,
                'originalSender' => $this->originalSender,
                'originalSubject' => $this->originalSubject,
                'isStored' => $this->isStored,
                'storeFailedDeliveries' => $this->storeFailedDeliveries,
                'recipientId' => $notifiable->id,
                'fingerprint' => $notifiable->should_encrypt ? $notifiable->fingerprint : null,
            ])
            ->withSymfonyMessage(function ($message) {
                $message->getHeaders()
                    ->addTextHeader('Feedback-ID', 'FDN:anonaddy');
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
