<?php

namespace App\Mail;

use App\CustomMailDriver\Mime\Part\CustomDataPart;
use App\Models\Alias;
use App\Models\EmailData;
use App\Models\User;
use App\Notifications\FailedDeliveryNotification;
use App\Traits\ApplyUserRules;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Email;
use Throwable;

class ReplyToEmail extends Mailable implements ShouldBeEncrypted, ShouldQueue
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

    protected $emailSubject;

    protected $emailText;

    protected $emailHtml;

    protected $emailAttachments;

    protected $emailInlineAttachments;

    protected $encryptedParts;

    protected $displayFrom;

    protected $fromEmail;

    protected $size;

    protected $inReplyTo;

    protected $references;

    protected $verpDomain;

    protected $ruleIds;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, Alias $alias, EmailData $emailData, $ruleIds = null)
    {
        $this->user = $user;
        $this->alias = $alias;
        $this->sender = $emailData->sender;

        $this->ccs = $emailData->ccs;
        $this->tos = $emailData->tos;

        // Replace alias reply/send CCs back to proper emails
        if (count($this->ccs)) {
            $this->ccs = collect($this->ccs)
                ->map(function ($cc) {
                    return [
                        'display' => null,
                        'address' => Str::replaceLast('=', '@', Str::between($cc['address'], $this->alias->local_part.'+', '@'.$this->alias->domain)),
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

        // Replace alias reply/send Tos back to proper emails
        if (count($this->tos)) {
            $this->tos = collect($this->tos)
                ->map(function ($to) {
                    return [
                        'display' => null,
                        'address' => Str::replaceLast('=', '@', Str::between($to['address'], $this->alias->local_part.'+', '@'.$this->alias->domain)),
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
        }

        $this->emailSubject = $emailData->subject;
        $this->emailText = $emailData->text;
        $this->emailHtml = $emailData->html;
        $this->emailAttachments = $emailData->attachments;
        $this->emailInlineAttachments = $emailData->inlineAttachments;
        $this->encryptedParts = $emailData->encryptedParts ?? null;
        $this->displayFrom = $alias->getFromName();
        $this->size = $emailData->size;
        $this->inReplyTo = $emailData->inReplyTo;
        $this->references = $emailData->references;
        $this->ruleIds = $ruleIds;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->fromEmail = $this->alias->email;

        if ($this->alias->isCustomDomain()) {
            if (! $this->alias->aliasable->isVerifiedForSending()) {
                $this->fromEmail = config('mail.from.address');
                $this->verpDomain = config('anonaddy.domain');
            }
        }

        $this->email = $this
            ->from($this->fromEmail, $this->displayFrom)
            ->subject(base64_decode($this->emailSubject))
            ->withSymfonyMessage(function (Email $message) {

                $message->getHeaders()
                    ->addTextHeader('Feedback-ID', 'R:'.$this->alias->id.':anonaddy');

                // Message-ID is replaced on replies as it can leak parts of the real email
                $message->getHeaders()->remove('Message-ID');
                $message->getHeaders()
                    ->addIdHeader('Message-ID', bin2hex(random_bytes(16)).'@'.$this->alias->domain);

                if ($this->inReplyTo) {
                    $message->getHeaders()
                        ->addTextHeader('In-Reply-To', base64_decode($this->inReplyTo));
                }

                if ($this->references) {
                    $message->getHeaders()
                        ->addTextHeader('References', base64_decode($this->references));
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
            });

        if ($this->emailText) {
            $this->email->text('emails.reply.text')->with([
                'text' => $this->removeRealEmailAndTextBanner(base64_decode($this->emailText)),
            ]);
        }

        if ($this->emailHtml) {
            $this->email->view('emails.reply.html')->with([
                'html' => $this->removeRealEmailAndHtmlBanner(base64_decode($this->emailHtml)),
            ]);
        }

        // To prevent invalid view error where no text or html is present...
        if (! $this->emailHtml && ! $this->emailText) {
            $this->email->text('emails.reply.text')->with([
                'text' => base64_decode($this->emailText),
            ]);
        }

        if ($this->ruleIds) {
            $this->applyRulesByIds($this->ruleIds);
        }

        $this->email->with([
            'userId' => $this->user->id,
            'aliasId' => $this->alias->id,
            'emailType' => 'R',
            'shouldBlock' => $this->size === 0,
            'encryptedParts' => $this->encryptedParts,
            'needsDkimSignature' => $this->needsDkimSignature(),
            'aliasDomain' => $this->alias->domain,
            'verpDomain' => $this->verpDomain ?? $this->alias->domain,
            'ccs' => $this->ccs,
            'tos' => $this->tos,
        ]);

        if ($this->alias->isCustomDomain() && ! $this->needsDkimSignature()) {
            $this->email->replyTo($this->alias->email, $this->displayFrom);
        }

        if ($this->size > 0) {
            if ($this->user->save_alias_last_used) {
                $this->alias->increment('emails_replied', 1, ['last_replied' => now()]);
            } else {
                $this->alias->increment('emails_replied');
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
        $this->user->defaultRecipient->notify(new FailedDeliveryNotification($this->alias->email, $this->sender, base64_decode($this->emailSubject)));

        if ($this->size > 0) {
            if ($this->alias->emails_replied > 0) {
                $this->alias->decrement('emails_replied');
            }

            if ($this->user->bandwidth > $this->size) {
                $this->user->bandwidth -= $this->size;
                $this->user->save();
            }
        }

        $this->user->failedDeliveries()->create([
            'recipient_id' => null,
            'alias_id' => $this->alias->id,
            'bounce_type' => null,
            'remote_mta' => null,
            'sender' => $this->sender,
            'email_type' => 'R',
            'status' => null,
            'code' => $exception->getMessage(),
            'attempted_at' => now(),
        ]);
    }

    private function needsDkimSignature()
    {
        return $this->alias->isCustomDomain() ? $this->alias->aliasable->isVerifiedForSending() : false;
    }

    private function removeRealEmailAndTextBanner($text)
    {
        // Replace <alias+hello=example.com@johndoe.anonaddy.com> with <hello@example.com>
        $destination = $this->email->to[0]['address'];

        // Reply may be HTML but email client added HTML banner plain text version
        return Str::of(str_ireplace($this->sender, '', $text))
            ->replace($this->alias->local_part.'+'.Str::replaceLast('@', '=', $destination).'@'.$this->alias->domain, $destination)
            ->replaceMatches('/(?s)((<|&lt;)!--banner-info--(&gt;|>)).*?((<|&lt;)!--banner-info--(&gt;|>))/mi', '')
            ->replaceMatches('/(This email was sent to).*?(to deactivate this alias)/mis', '');
    }

    private function removeRealEmailAndHtmlBanner($html)
    {
        // Replace <alias+hello=example.com@johndoe.anonaddy.com> with <hello@example.com>
        $destination = $this->email->to[0]['address'];

        // Reply may be HTML but have a plain text banner
        return Str::of(str_ireplace($this->sender, '', $html))
            ->replace($this->alias->local_part.'+'.Str::replaceLast('@', '=', $destination).'@'.$this->alias->domain, $destination)
            ->replaceMatches('/(?s)((<|&lt;)!--banner-info--(&gt;|>)).*?((<|&lt;)!--banner-info--(&gt;|>))/mi', '')
            ->replaceMatches('/(?s)(<tr((?!<tr).)*?'.preg_quote(Str::of(config('app.url'))->after('://')->rtrim('/'), '/')."(\/|%2F)deactivate(\/|%2F).*?\/tr>)/mi", '');
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
