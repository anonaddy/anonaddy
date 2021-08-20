<?php

namespace App\Notifications;

use App\Helpers\OpenPGPSigner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Swift_SwiftException;

class FailedDeliveryNotification extends Notification implements ShouldQueue, ShouldBeEncrypted
{
    use Queueable;

    protected $aliasEmail;
    protected $originalSender;
    protected $originalSubject;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($aliasEmail, $originalSender, $originalSubject)
    {
        $this->aliasEmail = $aliasEmail;
        $this->originalSender = $originalSender;
        $this->originalSubject = $originalSubject;
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
        $openpgpsigner = null;
        $fingerprint = $notifiable->should_encrypt ? $notifiable->fingerprint : null;

        if ($fingerprint) {
            try {
                $openpgpsigner = OpenPGPSigner::newInstance(config('anonaddy.signing_key_fingerprint'), [], "~/.gnupg");
                $openpgpsigner->addRecipient($fingerprint);
            } catch (Swift_SwiftException $e) {
                info($e->getMessage());
                $openpgpsigner = null;

                $notifiable->update(['should_encrypt' => false]);

                $notifiable->notify(new GpgKeyExpired);
            }
        }

        return (new MailMessage)
                    ->subject("New failed delivery on AnonAddy")
                    ->markdown('mail.failed_delivery_notification', [
                        'aliasEmail' => $this->aliasEmail,
                        'originalSender' => $this->originalSender,
                        'originalSubject' => $this->originalSubject
                    ])
                    ->withSwiftMessage(function ($message) use ($openpgpsigner) {
                        $message->getHeaders()
                                ->addTextHeader('Feedback-ID', 'FDN:anonaddy');

                        if ($openpgpsigner) {
                            $message->attachSigner($openpgpsigner);
                        }
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
