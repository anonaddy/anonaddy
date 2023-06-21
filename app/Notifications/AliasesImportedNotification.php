<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Symfony\Component\Mime\Email;

class AliasesImportedNotification extends Notification implements ShouldQueue, ShouldBeEncrypted
{
    use Queueable;

    protected $totalRows;

    protected $totalImported;

    protected $totalNotImported;

    protected $totalFailures;

    protected $totalErrors;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($totalRows, $totalImported, $totalNotImported, $totalFailures, $totalErrors)
    {
        $this->totalRows = $totalRows;
        $this->totalImported = $totalImported;
        $this->totalNotImported = $totalNotImported;
        $this->totalFailures = $totalFailures;
        $this->totalErrors = $totalErrors;
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
        $recipient = $notifiable->defaultRecipient;
        $fingerprint = $recipient->should_encrypt ? $recipient->fingerprint : null;

        return (new MailMessage())
            ->subject('Your aliases import has finished')
            ->markdown('mail.aliases_import_finished', [
                'totalRows' => $this->totalRows,
                'totalImported' => $this->totalImported,
                'totalNotImported' => $this->totalNotImported,
                'totalFailures' => $this->totalFailures,
                'totalErrors' => $this->totalErrors,
                'userId' => $notifiable->id,
                'recipientId' => $recipient->id,
                'emailType' => 'AIF',
                'hasVerifiedEmail' => $recipient->hasVerifiedEmail(),
                'fingerprint' => $fingerprint,
            ])
            ->withSymfonyMessage(function (Email $message) {
                $message->getHeaders()
                    ->addTextHeader('Feedback-ID', 'AIF:anonaddy');
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
