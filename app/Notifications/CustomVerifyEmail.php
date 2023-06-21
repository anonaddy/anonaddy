<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\URL;
use Symfony\Component\Mime\Email;

class CustomVerifyEmail extends VerifyEmail implements ShouldQueue, ShouldBeEncrypted
{
    use Queueable;

    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        if (static::$toMailCallback) {
            return call_user_func(static::$toMailCallback, $notifiable, $verificationUrl);
        }

        $feedbackId = $notifiable instanceof User ? 'VU:anonaddy' : 'VR:anonaddy';
        $recipientId = $notifiable instanceof User ? $notifiable->default_recipient_id : $notifiable->id;

        return (new MailMessage())
            ->subject(Lang::get('Verify Email Address'))
            ->markdown('mail.verify_email', [
                'verificationUrl' => $verificationUrl,
                'recipientId' => $recipientId,
            ])
            ->withSymfonyMessage(function (Email $message) use ($feedbackId) {
                $message->getHeaders()
                    ->addTextHeader('Feedback-ID', $feedbackId);
            });
    }

    /**
     * Get the verification URL for the given notifiable.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    protected function verificationUrl($notifiable)
    {
        if (static::$createUrlCallback) {
            return call_user_func(static::$createUrlCallback, $notifiable);
        }

        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => base64_encode(Hash::make($notifiable->getEmailForVerification())),
            ]
        );
    }
}
