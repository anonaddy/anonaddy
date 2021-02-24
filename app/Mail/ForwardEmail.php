<?php

namespace App\Mail;

use App\Helpers\AlreadyEncryptedSigner;
use App\Helpers\OpenPGPSigner;
use App\Models\Alias;
use App\Models\EmailData;
use App\Models\Recipient;
use App\Notifications\GpgKeyExpired;
use App\Traits\CheckUserRules;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Swift_Signers_DKIMSigner;
use Swift_SwiftException;

class ForwardEmail extends Mailable implements ShouldQueue, ShouldBeEncrypted
{
    use Queueable, SerializesModels, CheckUserRules;

    protected $email;
    protected $user;
    protected $alias;
    protected $sender;
    protected $displayFrom;
    protected $replyToAddress;
    protected $emailSubject;
    protected $emailText;
    protected $emailHtml;
    protected $emailAttachments;
    protected $deactivateUrl;
    protected $bannerLocation;
    protected $fingerprint;
    protected $openpgpsigner;
    protected $dkimSigner;
    protected $encryptedParts;
    protected $fromEmail;
    protected $size;
    protected $messageId;
    protected $inReplyTo;
    protected $references;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Alias $alias, EmailData $emailData, Recipient $recipient)
    {
        $this->user = $alias->user;
        $this->alias = $alias;
        $this->sender = $emailData->sender;
        $this->displayFrom = $emailData->display_from;
        $this->replyToAddress = $emailData->reply_to_address ?? $this->sender;
        $this->emailSubject = $emailData->subject;
        $this->emailText = $emailData->text;
        $this->emailHtml = $emailData->html;
        $this->emailAttachments = $emailData->attachments;
        $this->deactivateUrl = URL::signedRoute('deactivate', ['alias' => $alias->id]);
        $this->size = $emailData->size;
        $this->messageId = $emailData->messageId;
        $this->inReplyTo = $emailData->inReplyTo;
        $this->references = $emailData->references;
        $this->encryptedParts = $emailData->encryptedParts ?? null;

        $fingerprint = $recipient->should_encrypt && !$this->isAlreadyEncrypted() ? $recipient->fingerprint : null;

        $this->bannerLocation = $this->isAlreadyEncrypted() ? 'off' : $this->alias->user->banner_location;

        if ($this->fingerprint = $fingerprint) {
            try {
                $this->openpgpsigner = OpenPGPSigner::newInstance(config('anonaddy.signing_key_fingerprint'), [], "~/.gnupg");
                $this->openpgpsigner->addRecipient($fingerprint);
            } catch (Swift_SwiftException $e) {
                info($e->getMessage());
                $this->openpgpsigner = null;

                $recipient->update(['should_encrypt' => false]);

                $recipient->notify(new GpgKeyExpired);
            }
        }
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // Check if the user is using the old reply-to and from headers
        if ($this->user->use_reply_to) {
            $this->fromEmail = $this->alias->email;

            $replyToEmail = $this->alias->local_part . '+' . Str::replaceLast('@', '=', $this->replyToAddress) . '@' . $this->alias->domain;
        } else {
            $this->fromEmail = $this->alias->local_part . '+' . Str::replaceLast('@', '=', $this->replyToAddress) . '@' . $this->alias->domain;
        }

        $returnPath = $this->alias->email;

        if ($this->alias->isCustomDomain()) {
            if ($this->alias->aliasable->isVerifiedForSending()) {
                if (config('anonaddy.dkim_signing_key')) {
                    $this->dkimSigner = new Swift_Signers_DKIMSigner(config('anonaddy.dkim_signing_key'), $this->alias->domain, config('anonaddy.dkim_selector'));
                    $this->dkimSigner->ignoreHeader('List-Unsubscribe');
                    $this->dkimSigner->ignoreHeader('Return-Path');
                }
            } else {
                if (! isset($replyToEmail)) {
                    $replyToEmail = $this->fromEmail;
                }

                $this->fromEmail = config('mail.from.address');
                $returnPath = config('anonaddy.return_path');
            }
        }

        $this->email =  $this
            ->from($this->fromEmail, base64_decode($this->displayFrom)." '".$this->sender."'")
            ->subject($this->user->email_subject ?? base64_decode($this->emailSubject))
            ->text('emails.forward.text')->with([
                'text' => base64_decode($this->emailText)
            ])
            ->withSwiftMessage(function ($message) use ($returnPath) {
                $message->getHeaders()
                        ->addTextHeader('List-Unsubscribe', '<mailto:' . $this->alias->id . '@unsubscribe.' . config('anonaddy.domain') . '?subject=unsubscribe>, <' . $this->deactivateUrl . '>');

                $message->setReturnPath($returnPath);

                // This header is used to set the To: header as the alias just before sending.
                $message->getHeaders()
                        ->addTextHeader('Alias-To', $this->alias->email);

                if ($this->messageId) {
                    $message->getHeaders()->remove('Message-ID');

                    // We're not using $message->setId here because it can cause RFC exceptions
                    $message->getHeaders()
                            ->addTextHeader('Message-ID', base64_decode($this->messageId));
                } else {
                    $message->setId(bin2hex(random_bytes(16)).'@'.$this->alias->domain);
                }

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

                if ($this->openpgpsigner) {
                    $message->attachSigner($this->openpgpsigner);
                }

                if ($this->dkimSigner) {
                    $message->attachSigner($this->dkimSigner);
                }
            });

        if ($this->emailHtml) {
            $this->email->view('emails.forward.html')->with([
                'html' => base64_decode($this->emailHtml)
            ]);
        }

        foreach ($this->emailAttachments as $attachment) {
            $this->email->attachData(
                base64_decode($attachment['stream']),
                base64_decode($attachment['file_name']),
                ['mime' => base64_decode($attachment['mime'])]
            );
        }

        $this->replacedSubject = $this->user->email_subject ? ' with subject "' . base64_decode($this->emailSubject) . '"' : null;

        $this->checkRules();

        $this->email->with([
            'location' => $this->bannerLocation,
            'deactivateUrl' => $this->deactivateUrl,
            'aliasEmail' => $this->alias->email,
            'fromEmail' => $this->sender,
            'replacedSubject' => $this->replacedSubject,
            'shouldBlock' => $this->size === 0
        ]);

        if (isset($replyToEmail)) {
            $this->email->replyTo($replyToEmail);
        }

        if ($this->size > 0) {
            $this->alias->increment('emails_forwarded');

            $this->user->bandwidth += $this->size;
            $this->user->save();
        }

        return $this->email;
    }

    private function isAlreadyEncrypted()
    {
        return $this->encryptedParts || preg_match('/^-----BEGIN PGP MESSAGE-----([A-Za-z0-9+=\/\n]+)-----END PGP MESSAGE-----$/', base64_decode($this->emailText));
    }
}
