<?php

namespace App\Notifications;

use App\Helpers\OpenPGPSigner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Swift_SwiftException;

class SpamReplySendAttempt extends Notification implements ShouldQueue, ShouldBeEncrypted
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
        $this->aliasEmail = $alias['local_part'] . '@' . $alias['domain'];
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
        $openpgpsigner = null;
        $recipient = $notifiable->defaultRecipient;
        $fingerprint = $recipient->should_encrypt ? $recipient->fingerprint : null;

        if ($fingerprint) {
            try {
                $openpgpsigner = OpenPGPSigner::newInstance(config('anonaddy.signing_key_fingerprint'), [], "~/.gnupg");
                $openpgpsigner->addRecipient($fingerprint);
            } catch (Swift_SwiftException $e) {
                info($e->getMessage());
                $openpgpsigner = null;

                $recipient->update(['should_encrypt' => false]);

                $recipient->notify(new GpgKeyExpired);
            }
        }

        return (new MailMessage)
            ->subject('Attempted reply/send from alias has failed')
            ->markdown('mail.spam_reply_send_attempt', [
                'aliasEmail' => $this->aliasEmail,
                'recipient' => $this->recipient,
                'destination' => $this->destination,
                'authenticationResults' => $this->authenticationResults,
                'recipientId' => $notifiable->default_recipient_id
            ])
            ->withSwiftMessage(function ($message) use ($openpgpsigner) {
                $message->getHeaders()
                        ->addTextHeader('Feedback-ID', 'SRSA:anonaddy');

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
