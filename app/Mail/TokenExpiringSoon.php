<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;

class TokenExpiringSoon extends Mailable implements ShouldQueue, ShouldBeEncrypted
{
    use Queueable;
    use SerializesModels;

    protected $user;

    protected $recipient;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->recipient = $user->defaultRecipient;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->subject('Your AnonAddy API token expires soon')
            ->markdown('mail.token_expiring_soon', [
                'user' => $this->user,
                'recipientId' => $this->recipient->id,
                'fingerprint' => $this->recipient->should_encrypt ? $this->recipient->fingerprint : null,
            ])
            ->withSymfonyMessage(function (Email $message) {
                $message->getHeaders()
                    ->addTextHeader('Feedback-ID', 'TES:anonaddy');
            });
    }
}
