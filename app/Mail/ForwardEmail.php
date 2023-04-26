<?php

namespace App\Mail;

use App\CustomMailDriver\Mime\Part\InlineImagePart;
use App\Models\Alias;
use App\Models\EmailData;
use App\Models\Recipient;
use App\Notifications\FailedDeliveryNotification;
use App\Traits\CheckUserRules;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Email;

class ForwardEmail extends Mailable implements ShouldQueue, ShouldBeEncrypted
{
    use Queueable;
    use SerializesModels;
    use CheckUserRules;

    protected $email;

    protected $user;

    protected $alias;

    protected $sender;

    protected $originalCc;

    protected $originalTo;

    protected $displayFrom;

    protected $replyToAddress;

    protected $emailSubject;

    protected $emailText;

    protected $emailHtml;

    protected $emailAttachments;

    protected $emailInlineAttachments;

    protected $deactivateUrl;

    protected $bannerLocationText;

    protected $bannerLocationHtml;

    protected $fingerprint;

    protected $encryptedParts;

    protected $receivedHeaders;

    protected $fromEmail;

    protected $size;

    protected $messageId;

    protected $listUnsubscribe;

    protected $inReplyTo;

    protected $references;

    protected $originalEnvelopeFrom;

    protected $originalFromHeader;

    protected $originalReplyToHeader;

    protected $originalSenderHeader;

    protected $authenticationResults;

    protected $recipientId;

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
        $this->originalCc = $emailData->originalCc ?? null;
        $this->originalTo = $emailData->originalTo ?? null;
        $this->displayFrom = $emailData->display_from;
        $this->replyToAddress = $emailData->reply_to_address ?? $this->sender;
        $this->emailSubject = $emailData->subject;
        $this->emailText = $emailData->text;
        $this->emailHtml = $emailData->html;
        $this->emailAttachments = $emailData->attachments;
        $this->emailInlineAttachments = $emailData->inlineAttachments;
        $this->deactivateUrl = URL::signedRoute('deactivate', ['alias' => $alias->id]);
        $this->size = $emailData->size;
        $this->messageId = $emailData->messageId;
        $this->listUnsubscribe = $emailData->listUnsubscribe;
        $this->inReplyTo = $emailData->inReplyTo;
        $this->references = $emailData->references;
        $this->originalEnvelopeFrom = $emailData->originalEnvelopeFrom;
        $this->originalFromHeader = $emailData->originalFromHeader;
        $this->originalReplyToHeader = $emailData->originalReplyToHeader;
        $this->originalSenderHeader = $emailData->originalSenderHeader;
        $this->authenticationResults = $emailData->authenticationResults;
        $this->encryptedParts = $emailData->encryptedParts ?? null;
        $this->receivedHeaders = $emailData->receivedHeaders;
        $this->recipientId = $recipient->id;

        $this->fingerprint = $recipient->should_encrypt && ! $this->isAlreadyEncrypted() ? $recipient->fingerprint : null;

        $this->bannerLocationText = $this->bannerLocationHtml = $this->isAlreadyEncrypted() ? 'off' : $this->alias->user->banner_location;
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

            $replyToEmail = $this->alias->local_part.'+'.Str::replaceLast('@', '=', $this->replyToAddress).'@'.$this->alias->domain;
        } else {
            $this->fromEmail = $this->alias->local_part.'+'.Str::replaceLast('@', '=', $this->replyToAddress).'@'.$this->alias->domain;
        }

        $returnPath = $this->alias->email;

        if ($this->alias->isCustomDomain()) {
            if (! $this->alias->aliasable->isVerifiedForSending()) {
                if (! isset($replyToEmail)) {
                    $replyToEmail = $this->fromEmail;
                }

                $this->fromEmail = config('mail.from.address');
                $returnPath = config('anonaddy.return_path');
            }
        }

        $this->email = $this
            ->from($this->fromEmail, base64_decode($this->displayFrom)." '".$this->sender."'")
            ->subject($this->user->email_subject ?? base64_decode($this->emailSubject))
            ->withSymfonyMessage(function (Email $message) use ($returnPath) {
                $message->returnPath($returnPath);

                $message->getHeaders()
                    ->addTextHeader('Feedback-ID', 'F:'.$this->alias->id.':anonaddy');

                // This header is used to set the To: header as the alias just before sending.
                $message->getHeaders()
                    ->addTextHeader('Alias-To', $this->alias->email);

                $message->getHeaders()->remove('Message-ID');

                if ($this->messageId) {
                    $message->getHeaders()
                        ->addIdHeader('Message-ID', base64_decode($this->messageId));
                } else {
                    $message->getHeaders()
                        ->addIdHeader('Message-ID', bin2hex(random_bytes(16)).'@'.$this->alias->domain);
                }

                if ($this->listUnsubscribe) {
                    $message->getHeaders()
                        ->addTextHeader('List-Unsubscribe', base64_decode($this->listUnsubscribe));
                }

                if ($this->inReplyTo) {
                    $message->getHeaders()
                        ->addTextHeader('In-Reply-To', base64_decode($this->inReplyTo));
                }

                if ($this->references) {
                    $message->getHeaders()
                        ->addTextHeader('References', base64_decode($this->references));
                }

                if ($this->receivedHeaders) {
                    if (is_array($this->receivedHeaders)) {
                        foreach ($this->receivedHeaders as $receivedHeader) {
                            $message->getHeaders()
                                ->addTextHeader('Received', $receivedHeader);
                        }
                    } else {
                        $message->getHeaders()
                            ->addTextHeader('Received', $this->receivedHeaders);
                    }
                }

                if ($this->authenticationResults) {
                    $message->getHeaders()
                        ->addTextHeader('X-AnonAddy-Authentication-Results', $this->authenticationResults);
                }

                $message->getHeaders()
                    ->addTextHeader('X-AnonAddy-Original-Sender', $this->sender);

                $message->getHeaders()
                    ->addTextHeader('X-AnonAddy-Original-Envelope-From', $this->originalEnvelopeFrom);

                if ($this->originalFromHeader) {
                    $message->getHeaders()
                        ->addTextHeader('X-AnonAddy-Original-From-Header', base64_decode($this->originalFromHeader));
                }

                if ($this->originalReplyToHeader) {
                    $message->getHeaders()
                        ->addTextHeader('X-AnonAddy-Original-Reply-To-Header', base64_decode($this->originalReplyToHeader));
                }

                if ($this->originalSenderHeader) {
                    $message->getHeaders()
                        ->addTextHeader('Original-Sender', base64_decode($this->originalSenderHeader));
                }

                if ($this->emailInlineAttachments) {
                    foreach ($this->emailInlineAttachments as $attachment) {
                        $part = new InlineImagePart(base64_decode($attachment['stream']), base64_decode($attachment['file_name']), base64_decode($attachment['mime']));

                        $part->asInline();

                        $part->setContentId(base64_decode($attachment['contentId']));
                        $part->setFileName(base64_decode($attachment['file_name']));

                        $message->attachPart($part);
                    }
                }

                if ($this->originalCc) {
                    $message->getHeaders()
                        ->addTextHeader('X-AnonAddy-Original-Cc', $this->originalCc);
                }

                if ($this->originalTo) {
                    $message->getHeaders()
                        ->addTextHeader('X-AnonAddy-Original-To', $this->originalTo);
                }
            });

        if ($this->emailText) {
            $this->email->text('emails.forward.text')->with([
                'text' => base64_decode($this->emailText),
            ]);
        }

        if ($this->emailHtml) {
            // Turn off the banner for the plain text version
            $this->bannerLocationText = 'off';

            $this->email->view('emails.forward.html')->with([
                'html' => base64_decode($this->emailHtml),
            ]);
        }

        // To prevent invalid view error where no text or html is present...
        if (! $this->emailHtml && ! $this->emailText) {
            $this->email->text('emails.forward.text')->with([
                'text' => base64_decode($this->emailText),
            ]);
        }

        foreach ($this->emailAttachments as $attachment) {
            $this->email->attachData(
                base64_decode($attachment['stream']),
                base64_decode($attachment['file_name']),
                ['mime' => base64_decode($attachment['mime'])]
            );
        }

        $this->replacedSubject = $this->user->email_subject ? ' with subject "'.base64_decode($this->emailSubject).'"' : null;

        $this->checkRules('Forwards');

        $this->email->with([
            'locationText' => $this->bannerLocationText,
            'locationHtml' => $this->bannerLocationHtml,
            'deactivateUrl' => $this->deactivateUrl,
            'aliasEmail' => $this->alias->email,
            'aliasDomain' => $this->alias->domain,
            'aliasDescription' => $this->alias->description,
            'recipientId' => $this->recipientId,
            'fingerprint' => $this->fingerprint,
            'encryptedParts' => $this->encryptedParts,
            'fromEmail' => $this->sender,
            'replacedSubject' => $this->replacedSubject,
            'shouldBlock' => $this->size === 0,
            'needsDkimSignature' => $this->needsDkimSignature(),
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

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed()
    {
        // Send user failed delivery notification, add to failed deliveries table
        $recipient = Recipient::find($this->recipientId);

        $recipient->notify(new FailedDeliveryNotification($this->alias->email, $this->sender, base64_decode($this->emailSubject)));

        if ($this->size > 0) {
            if ($this->alias->emails_forwarded > 0) {
                $this->alias->decrement('emails_forwarded');
            }

            if ($this->user->bandwidth > $this->size) {
                $this->user->bandwidth -= $this->size;
                $this->user->save();
            }
        }

        $this->user->failedDeliveries()->create([
            'recipient_id' => $this->recipientId,
            'alias_id' => $this->alias->id,
            'bounce_type' => null,
            'remote_mta' => null,
            'sender' => $this->sender,
            'email_type' => 'F',
            'status' => null,
            'code' => 'An error has occurred, please check the logs.',
            'attempted_at' => now(),
        ]);
    }

    private function isAlreadyEncrypted()
    {
        return $this->encryptedParts || preg_match('/^-----BEGIN PGP MESSAGE-----([A-Za-z0-9+=\/\n]+)-----END PGP MESSAGE-----$/', base64_decode($this->emailText));
    }

    private function needsDkimSignature()
    {
        return $this->alias->isCustomDomain() ? $this->alias->aliasable->isVerifiedForSending() : false;
    }

    /**
     * Override default buildSubject method that does not allow an empty subject.
     */
    protected function buildSubject($message)
    {
        $message->subject($this->subject);

        return $this;
    }
}
