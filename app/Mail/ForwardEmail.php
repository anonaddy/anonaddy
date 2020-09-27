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
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Swift_Signers_DKIMSigner;
use Swift_SwiftException;

class ForwardEmail extends Mailable implements ShouldQueue
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
        $this->replyToAddress = $emailData->reply_to_address ?? null;
        $this->emailSubject = $emailData->subject;
        $this->emailText = $emailData->text;
        $this->emailHtml = $emailData->html;
        $this->emailAttachments = $emailData->attachments;
        $this->deactivateUrl = URL::signedRoute('deactivate', ['alias' => $alias->id]);
        $this->size = $emailData->size;

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
        $replyToEmail = $this->alias->local_part . '+' . Str::replaceLast('@', '=', $this->sender) . '@' . $this->alias->domain;

        if ($this->alias->isCustomDomain()) {
            if ($this->alias->aliasable->isVerifiedForSending()) {
                $this->fromEmail = $this->alias->email;
                $returnPath = $this->alias->email;

                $this->dkimSigner = new Swift_Signers_DKIMSigner(config('anonaddy.dkim_signing_key'), $this->alias->domain, config('anonaddy.dkim_selector'));
                $this->dkimSigner->ignoreHeader('List-Unsubscribe');
                $this->dkimSigner->ignoreHeader('Return-Path');
            } else {
                $this->fromEmail = config('mail.from.address');
                $returnPath = config('anonaddy.return_path');
            }
        } else {
            $this->fromEmail = $this->alias->email;
            $returnPath = 'mailer@'.$this->alias->parentDomain();
        }

        $this->email =  $this
            ->from($this->fromEmail, base64_decode($this->displayFrom)." '".$this->sender."'")
            ->replyTo($replyToEmail)
            ->subject($this->user->email_subject ?? base64_decode($this->emailSubject))
            ->text('emails.forward.text')->with([
                'text' => base64_decode($this->emailText)
            ])
            ->withSwiftMessage(function ($message) use ($returnPath) {
                $message->getHeaders()
                        ->addTextHeader('List-Unsubscribe', '<mailto:' . $this->alias->id . '@unsubscribe.' . config('anonaddy.domain') . '?subject=unsubscribe>, <' . $this->deactivateUrl . '>');

                $message->getHeaders()
                        ->addTextHeader('Return-Path', $returnPath);

                $message->setId(bin2hex(random_bytes(16)).'@'.$this->alias->domain);

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
