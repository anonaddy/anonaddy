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
            ->subject('Disallowed reply/send from alias')
            ->markdown('mail.disallowed_reply_send_attempt', [
                'aliasEmail' => $this->aliasEmail,
                'recipient' => $this->recipient,
                'destination' => $this->destination,
                'authenticationResults' => $this->authenticationResults
            ])
            ->withSwiftMessage(function ($message) use ($openpgpsigner) {
                $message->getHeaders()
                        ->addTextHeader('Feedback-ID', 'DRSA:anonaddy');

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
