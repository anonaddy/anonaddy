<?php

namespace App\Notifications;

use App\Helpers\OpenPGPSigner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Swift_SwiftException;

class NearBandwidthLimit extends Notification implements ShouldQueue, ShouldBeEncrypted
{
    use Queueable;

    protected $month;
    protected $reset;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->month = now()->format('F');
        $this->reset = now()->addMonthsNoOverflow(1)->startOfMonth()->format('jS F');
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
        ->subject("You're close to your bandwidth limit for ".$this->month)
        ->markdown('mail.near_bandwidth_limit', [
            'bandwidthUsage' => $notifiable->bandwidth_mb,
            'bandwidthLimit' => $notifiable->getBandwidthLimitMb(),
            'month' => $this->month,
            'reset' => $this->reset
        ])
        ->withSwiftMessage(function ($message) use ($openpgpsigner) {
            $message->getHeaders()
                    ->addTextHeader('Feedback-ID', 'NBL:anonaddy');

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
