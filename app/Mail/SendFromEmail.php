<?php

namespace App\Mail;

use App\Alias;
use App\EmailData;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Swift_Signers_DKIMSigner;

class SendFromEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $user;
    protected $alias;
    protected $emailSubject;
    protected $emailText;
    protected $emailHtml;
    protected $emailAttachments;
    protected $dkimSigner;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, Alias $alias, EmailData $emailData)
    {
        $this->user = $user;
        $this->alias = $alias;
        $this->emailSubject = $emailData->subject;
        $this->emailText = $emailData->text;
        $this->emailHtml = $emailData->html;
        $this->emailAttachments = $emailData->attachments;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $fromName = $this->user->from_name ?? null;

        if ($this->alias->isCustomDomain()) {
            if ($this->alias->aliasable->isVerifiedForSending()) {
                $fromEmail = $this->alias->email;
                $returnPath = $this->alias->email;

                $this->dkimSigner = new Swift_Signers_DKIMSigner(config('anonaddy.dkim_signing_key'), $this->alias->domain, config('anonaddy.dkim_selector'));
                $this->dkimSigner->ignoreHeader('Return-Path');
            } else {
                $fromEmail = config('mail.from.address');
                $returnPath = config('anonaddy.return_path');
            }
        } else {
            $fromEmail = $this->alias->email;
            $returnPath = 'mailer@'.$this->alias->parentDomain();
        }

        $email =  $this
            ->from($fromEmail, $fromName)
            ->subject(base64_decode($this->emailSubject))
            ->text('emails.reply.text')->with([
                'text' => base64_decode($this->emailText)
            ])
            ->withSwiftMessage(function ($message) use ($returnPath) {
                $message->getHeaders()
                        ->addTextHeader('Return-Path', config('anonaddy.return_path'));

                $message->setId(bin2hex(random_bytes(16)).'@'.$this->alias->domain);

                if ($this->dkimSigner) {
                    $message->attachSigner($this->dkimSigner);
                }
            });

        if ($this->alias->isCustomDomain() && !$this->dkimSigner) {
            $email->replyTo($this->alias->email, $fromName);
        }

        if ($this->emailHtml) {
            $email->view('emails.reply.html')->with([
                'html' => base64_decode($this->emailHtml)
            ]);
        }

        foreach ($this->emailAttachments as $attachment) {
            $email->attachData(
                base64_decode($attachment['stream']),
                base64_decode($attachment['file_name']),
                ['mime' => base64_decode($attachment['mime'])]
            );
        }

        return $email;
    }
}
