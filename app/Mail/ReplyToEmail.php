<?php

namespace App\Mail;

use App\Helpers\AlreadyEncryptedSigner;
use App\Models\Alias;
use App\Models\EmailData;
use App\Models\User;
use App\Notifications\FailedDeliveryNotification;
use App\Traits\CheckUserRules;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Swift_Signers_DKIMSigner;

class ReplyToEmail extends Mailable implements ShouldQueue, ShouldBeEncrypted
{
    use Queueable, SerializesModels, CheckUserRules;

    protected $email;
    protected $user;
    protected $alias;
    protected $sender;
    protected $emailSubject;
    protected $emailText;
    protected $emailHtml;
    protected $emailAttachments;
    protected $dkimSigner;
    protected $encryptedParts;
    protected $displayFrom;
    protected $fromEmail;
    protected $size;
    protected $inReplyTo;
    protected $references;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, Alias $alias, EmailData $emailData)
    {
        $this->user = $user;
        $this->alias = $alias;
        $this->sender = $emailData->sender;
        $this->emailSubject = $emailData->subject;
        $this->emailText = $emailData->text;
        $this->emailHtml = $emailData->html;
        $this->emailAttachments = $emailData->attachments;
        $this->encryptedParts = $emailData->encryptedParts ?? null;
        $this->displayFrom = $user->from_name ?? null;
        $this->size = $emailData->size;
        $this->inReplyTo = $emailData->inReplyTo;
        $this->references = $emailData->references;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $returnPath = $this->alias->email;

        if ($this->alias->isCustomDomain()) {
            if ($this->alias->aliasable->isVerifiedForSending()) {
                $this->fromEmail = $this->alias->email;

                if (config('anonaddy.dkim_signing_key')) {
                    $this->dkimSigner = new Swift_Signers_DKIMSigner(config('anonaddy.dkim_signing_key'), $this->alias->domain, config('anonaddy.dkim_selector'));
                    $this->dkimSigner->ignoreHeader('List-Unsubscribe');
                    $this->dkimSigner->ignoreHeader('Return-Path');
                    $this->dkimSigner->ignoreHeader('Feedback-ID');
                    $this->dkimSigner->ignoreHeader('Content-Type');
                    $this->dkimSigner->ignoreHeader('Content-Description');
                    $this->dkimSigner->ignoreHeader('Content-Disposition');
                    $this->dkimSigner->ignoreHeader('Content-Transfer-Encoding');
                    $this->dkimSigner->ignoreHeader('MIME-Version');
                }
            } else {
                $this->fromEmail = config('mail.from.address');
                $returnPath = config('anonaddy.return_path');
            }
        } else {
            $this->fromEmail = $this->alias->email;
        }

        $this->email =  $this
            ->from($this->fromEmail, $this->displayFrom)
            ->subject(base64_decode($this->emailSubject))
            ->text('emails.reply.text')->with([
                'text' => str_ireplace($this->sender, '', base64_decode($this->emailText))
            ])
            ->withSwiftMessage(function ($message) use ($returnPath) {
                $message->setReturnPath($returnPath);

                $message->getHeaders()
                        ->addTextHeader('Feedback-ID', 'R:' . $this->alias->id . ':anonaddy');

                // Message-ID is replaced on replies as it can leak parts of the real email
                $message->setId(bin2hex(random_bytes(16)).'@'.$this->alias->domain);

                if ($this->inReplyTo) {
                    $message->getHeaders()
                            ->addTextHeader('In-Reply-To', base64_decode($this->inReplyTo));
                }

                if ($this->references) {
                    $message->getHeaders()
                            ->addTextHeader('References', base64_decode($this->references));
                }

                if ($this->encryptedParts) {
                    $alreadyEncryptedSigner = new AlreadyEncryptedSigner($this->encryptedParts);

                    $message->attachSigner($alreadyEncryptedSigner);
                }

                if ($this->dkimSigner) {
                    $message->attachSigner($this->dkimSigner);
                }
            });

        if ($this->emailHtml) {
            $this->email->view('emails.reply.html')->with([
                'html' => str_ireplace($this->sender, '', base64_decode($this->emailHtml))
            ]);
        }

        foreach ($this->emailAttachments as $attachment) {
            $this->email->attachData(
                base64_decode($attachment['stream']),
                base64_decode($attachment['file_name']),
                ['mime' => base64_decode($attachment['mime'])]
            );
        }

        $this->checkRules();

        $this->email->with([
            'shouldBlock' => $this->size === 0
        ]);

        if ($this->alias->isCustomDomain() && !$this->dkimSigner) {
            $this->email->replyTo($this->alias->email, $this->displayFrom);
        }

        if ($this->size > 0) {
            $this->alias->increment('emails_replied');

            $this->user->bandwidth += $this->size;
            $this->user->save();
        }

        return $this->email;
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed()
    {
        // Send user failed delivery notification, add to failed deliveries table
        $this->user->defaultRecipient->notify(new FailedDeliveryNotification($this->alias->email, $this->sender, base64_decode($this->emailSubject)));

        $this->user->failedDeliveries()->create([
            'recipient_id' => null,
            'alias_id' => $this->alias->id,
            'bounce_type' => null,
            'remote_mta' => null,
            'sender' => $this->sender,
            'email_type' => 'R',
            'status' => null,
            'code' => 'An error has occurred, please check the logs.',
            'attempted_at' => now()
        ]);
    }
}
