<?php

namespace App\Mail;

use App\CustomMailDriver\Mime\Part\CustomDataPart;
use App\Enums\DisplayFromFormat;
use App\Models\Alias;
use App\Models\EmailData;
use App\Models\Recipient;
use App\Notifications\FailedDeliveryNotification;
use App\Traits\ApplyUserRules;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Email;
use Throwable;

class ForwardEmail extends Mailable implements ShouldBeEncrypted, ShouldQueue
{
    use ApplyUserRules;
    use Queueable;
    use SerializesModels;

    protected $email;

    protected $user;

    protected $alias;

    protected $sender;

    protected $ccs;

    protected $tos;

    protected $originalCc;

    protected $originalTo;

    protected $displayFrom;

    protected $replyToAddress;

    protected $emailSubject;

    protected $replacedSubject;

    protected $emailText;

    protected $emailHtml;

    protected $emailAttachments;

    protected $emailInlineAttachments;

    protected $deactivateUrl;

    protected $bannerLocationText;

    protected $bannerLocationHtml;

    protected $isSpam;

    protected $failedDmarc;

    protected $resend;

    protected $resendFromEmail;

    protected $fingerprint;

    protected $encryptedParts;

    protected $isInlineEncrypted;

    protected $receivedHeaders;

    protected $fromEmail;

    protected $size;

    protected $messageId;

    protected $listUnsubscribe;

    protected $listUnsubscribePost;

    protected $inReplyTo;

    protected $references;

    protected $originalEnvelopeFrom;

    protected $originalFromHeader;

    protected $originalReplyToHeader;

    protected $originalSenderHeader;

    protected $authenticationResults;

    protected $recipientId;

    protected $verpDomain;

    protected $ruleIds;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Alias $alias, EmailData $emailData, Recipient $recipient, $resend = false, $ruleIds = null)
    {
        $this->user = $alias->user;
        $this->alias = $alias;
        $this->sender = $emailData->sender;
        $this->ccs = $emailData->ccs;
        $this->tos = $emailData->tos;
        $this->originalCc = $emailData->originalCc ?? null;
        $this->originalTo = $emailData->originalTo ?? null;

        // Create and swap with alias reply-to addresses to allow easy reply-all
        if (count($this->ccs)) {
            $this->ccs = collect($this->ccs)
                ->map(function ($cc) use ($resend) {
                    // Leave alias email Cc as it is
                    if (stripEmailExtension($cc['address']) === $this->alias->email && ! $resend) {
                        return [
                            'display' => $cc['display'] != $cc['address'] ? $cc['display'] : null,
                            'address' => $this->alias->email,
                        ];
                    }

                    return [
                        'display' => $cc['display'] != $cc['address'] ? $cc['display'] : null,
                        'address' => $resend ? $cc['address'] : $this->alias->local_part.'+'.Str::replaceLast('@', '=', $cc['address']).'@'.$this->alias->domain,
                    ];
                })
                ->filter(fn ($cc) => filter_var($cc['address'], FILTER_VALIDATE_EMAIL))
                ->map(function ($cc) {
                    // Only add in display if it exists
                    if ($cc['display']) {
                        return $cc['display'].' <'.$cc['address'].'>';
                    }

                    return '<'.$cc['address'].'>';
                })
                ->toArray();
        }

        // Create and swap with alias reply-to addresses to allow easy reply-all
        $this->tos = collect($this->tos)
            ->when(! count($this->tos), function ($tos) {
                return $tos->push([
                    'display' => null,
                    'address' => $this->alias->email,
                ]);
            })
            ->map(function ($to) use ($resend) {
                // Leave alias email To as it is
                if (stripEmailExtension($to['address']) === $this->alias->email && ! $resend) {
                    return [
                        'display' => $to['display'] != $to['address'] ? $to['display'] : null,
                        'address' => $this->alias->email,
                    ];
                }

                return [
                    'display' => $to['display'] != $to['address'] ? $to['display'] : null,
                    'address' => $resend ? $to['address'] : $this->alias->local_part.'+'.Str::replaceLast('@', '=', $to['address']).'@'.$this->alias->domain,
                ];
            })
            ->filter(fn ($to) => filter_var($to['address'], FILTER_VALIDATE_EMAIL))
            ->map(function ($to) {
                // Only add in display if it exists
                if ($to['display']) {
                    return $to['display'].' <'.$to['address'].'>';
                }

                return '<'.$to['address'].'>';
            })
            ->toArray();

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
        $this->listUnsubscribePost = $emailData->listUnsubscribePost;
        $this->inReplyTo = $emailData->inReplyTo;
        $this->references = $emailData->references;
        $this->originalEnvelopeFrom = $emailData->originalEnvelopeFrom;
        $this->originalFromHeader = $emailData->originalFromHeader;
        $this->originalReplyToHeader = $emailData->originalReplyToHeader;
        $this->originalSenderHeader = $emailData->originalSenderHeader;
        $this->authenticationResults = $emailData->authenticationResults;
        $this->encryptedParts = $emailData->encryptedParts ?? null;
        $this->isInlineEncrypted = $emailData->isInlineEncrypted ?? false;
        $this->receivedHeaders = $emailData->receivedHeaders;
        $this->recipientId = $recipient->id;

        $this->fingerprint = $recipient->should_encrypt && ! $this->isAlreadyEncrypted() ? $recipient->fingerprint : null;

        $this->bannerLocationText = $this->bannerLocationHtml = $this->isAlreadyEncrypted() || $resend ? 'off' : $this->alias->user->banner_location;
        $this->ruleIds = $ruleIds;
        $this->isSpam = $emailData->isSpam;
        $this->failedDmarc = $emailData->failedDmarc;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // Check if the user is using the old reply-to and from headers
        if ($this->resendFromEmail) {
            $this->fromEmail = $this->resendFromEmail;
        } elseif ($this->user->use_reply_to) {
            $this->fromEmail = $this->alias->email;

            $replyToEmail = $this->alias->local_part.'+'.Str::replaceLast('@', '=', $this->replyToAddress).'@'.$this->alias->domain;
        } else {
            $this->fromEmail = $this->alias->local_part.'+'.Str::replaceLast('@', '=', $this->replyToAddress).'@'.$this->alias->domain;
        }

        if ($this->alias->isCustomDomain()) {
            if (! $this->alias->aliasable->isVerifiedForSending()) {
                if (! isset($replyToEmail)) {
                    $replyToEmail = $this->fromEmail;
                }

                $this->fromEmail = config('mail.from.address');
                $this->verpDomain = config('anonaddy.domain');
            }
        }

        $displayFrom = $this->getUserDisplayFrom(base64_decode($this->displayFrom));

        $spamWarningBehaviour = $this->user->spam_warning_behaviour;
        $showSpamBanner = ($this->isSpam || $this->failedDmarc) && $spamWarningBehaviour === 'banner';

        $subject = $this->user->email_subject ?? base64_decode($this->emailSubject);
        if (($this->isSpam || $this->failedDmarc) && $spamWarningBehaviour === 'subject') {
            $prefix = $this->failedDmarc ? '[DMARC FAIL]' : '[SPAM]';
            $subject = $prefix.' '.$subject;
        }

        $this->email = $this
            ->from($this->fromEmail, $displayFrom)
            ->subject($subject)
            ->withSymfonyMessage(function (Email $message) {

                $message->getHeaders()
                    ->addTextHeader('Feedback-ID', 'F:'.$this->alias->id.':anonaddy');

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

                    // Only check if has original List-Unsubscribe
                    if ($this->listUnsubscribePost) {
                        $message->getHeaders()
                            ->addTextHeader('List-Unsubscribe-Post', base64_decode($this->listUnsubscribePost));
                    }
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
                        $part = new CustomDataPart(base64_decode($attachment['stream']), base64_decode($attachment['file_name']), base64_decode($attachment['mime']));

                        $part->asInline();

                        $part->setContentId(base64_decode($attachment['contentId']));
                        $part->setFileName(base64_decode($attachment['file_name']));

                        $message->addPart($part);
                    }
                }

                if ($this->emailAttachments) {
                    foreach ($this->emailAttachments as $attachment) {
                        $part = new CustomDataPart(base64_decode($attachment['stream']), base64_decode($attachment['file_name']), base64_decode($attachment['mime']));

                        // Only set content-id if present
                        if ($attachment['contentId']) {
                            $part->setContentId(base64_decode($attachment['contentId']));
                        }
                        $part->setFileName(base64_decode($attachment['file_name']));

                        $message->addPart($part);
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

                if ($this->isSpam) {
                    $message->getHeaders()
                        ->addTextHeader('X-AnonAddy-Spam', 'Yes');
                }

                if ($this->resend) {
                    $message->getHeaders()
                        ->addTextHeader('X-AnonAddy-Resend', 'Yes');
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

        // No HTML content but showing spam/DMARC banner, then force html version
        if (! $this->emailHtml && $showSpamBanner) {
            // Turn off the banner for the plain text version
            $this->bannerLocationText = 'off';

            $this->email->view('emails.forward.html')->with([
                'html' => base64_decode($this->emailText),
            ]);
        }

        // To prevent invalid view error where no text or html is present...
        if (! $this->emailHtml && ! $this->emailText) {
            $this->email->text('emails.forward.text')->with([
                'text' => base64_decode($this->emailText),
            ]);
        }

        $this->replacedSubject = $this->user->email_subject ? ' with subject "'.base64_decode($this->emailSubject).'"' : null;

        if ($this->ruleIds) {
            $this->applyRulesByIds($this->ruleIds);
        }

        $this->email->with([
            'locationText' => $this->bannerLocationText,
            'locationHtml' => $this->bannerLocationHtml,
            'isSpam' => $this->isSpam,
            'failedDmarc' => $this->failedDmarc,
            'showSpamBanner' => $showSpamBanner,
            'deactivateUrl' => $this->deactivateUrl,
            'aliasEmail' => $this->alias->email,
            'aliasDomain' => $this->alias->domain,
            'aliasDescription' => $this->alias->description,
            'userId' => $this->user->id,
            'aliasId' => $this->alias->id,
            'recipientId' => $this->recipientId,
            'emailType' => 'F',
            'fingerprint' => $this->fingerprint,
            'encryptedParts' => $this->encryptedParts,
            'fromEmail' => $this->sender,
            'replacedSubject' => $this->replacedSubject,
            'shouldBlock' => $this->size === 0,
            'needsDkimSignature' => $this->needsDkimSignature(),
            'verpDomain' => $this->verpDomain ?? $this->alias->domain,
            'ccs' => $this->ccs,
            'tos' => $this->tos,
        ]);

        if (isset($replyToEmail)) {
            $this->email->replyTo($replyToEmail);
        }

        if ($this->size > 0) {
            if ($this->user->save_alias_last_used) {
                $this->alias->increment('emails_forwarded', 1, ['last_forwarded' => now()]);
            } else {
                $this->alias->increment('emails_forwarded');
            }

            $this->user->bandwidth += $this->size;
            $this->user->save();
        }

        return $this->email;
    }

    /**
     * Handle a job failure.
     *
     * @return void
     */
    public function failed(Throwable $exception)
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
            'code' => $exception->getMessage(),
            'attempted_at' => now(),
        ]);
    }

    private function getUserDisplayFrom($displayFrom)
    {
        // If there is no display from name
        if ($displayFrom === $this->sender) {
            return match ($this->user->display_from_format) {
                DisplayFromFormat::BRACKETS => str_replace('@', '(a)', $this->sender),
                DisplayFromFormat::DOMAINONLY => Str::afterLast($this->sender, '@'),
                DisplayFromFormat::NONE => null,
                default => str_replace('@', ' at ', $this->sender),
            };
        }

        // Check user display_from_format settings and then return correct format
        return match ($this->user->display_from_format) {
            DisplayFromFormat::DEFAULT => str_replace('@', ' at ', $displayFrom." '".$this->sender."'"),
            DisplayFromFormat::BRACKETS => str_replace('@', '(a)', $displayFrom.' - '.$this->sender),
            DisplayFromFormat::DOMAIN => str_replace('@', ' at ', $displayFrom.' - '.Str::afterLast($this->sender, '@')),
            DisplayFromFormat::NAME => str_replace('@', ' at ', $displayFrom),
            DisplayFromFormat::ADDRESS => str_replace('@', ' at ', $this->sender),
            DisplayFromFormat::DOMAINONLY => Str::afterLast($this->sender, '@'),
            DisplayFromFormat::NONE => null,
            DisplayFromFormat::LEGACY => $displayFrom." '".$this->sender."'",
            default => str_replace('@', ' at ', $displayFrom." '".$this->sender."'"),
        };
    }

    private function isAlreadyEncrypted()
    {
        return $this->encryptedParts || $this->isInlineEncrypted;
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
