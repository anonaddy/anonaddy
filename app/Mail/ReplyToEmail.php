<?php

namespace App\Mail;

use App\CustomMailDriver\Mime\Part\InlineImagePart;
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
use Illuminate\Support\Str;
use Symfony\Component\Mime\Email;

class ReplyToEmail extends Mailable implements ShouldQueue, ShouldBeEncrypted
{
    use Queueable;
    use SerializesModels;
    use CheckUserRules;

    protected $email;

    protected $user;

    protected $alias;

    protected $sender;

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
        $this->emailInlineAttachments = $emailData->inlineAttachments;
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
        $this->fromEmail = $this->alias->email;

        if ($this->alias->isCustomDomain()) {
            if (! $this->alias->aliasable->isVerifiedForSending()) {
                $this->fromEmail = config('mail.from.address');
                $returnPath = config('anonaddy.return_path');
            }
        }

        $this->email = $this
            ->from($this->fromEmail, $this->displayFrom)
            ->subject(base64_decode($this->emailSubject))
            ->withSymfonyMessage(function (Email $message) use ($returnPath) {
                $message->returnPath($returnPath);

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
                        $part = new InlineImagePart(base64_decode($attachment['stream']), base64_decode($attachment['file_name']), base64_decode($attachment['mime']));

                        $part->asInline();

                        $part->setContentId(base64_decode($attachment['contentId']));
                        $part->setFileName(base64_decode($attachment['file_name']));

                        $message->attachPart($part);
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

        foreach ($this->emailAttachments as $attachment) {
            $this->email->attachData(
                base64_decode($attachment['stream']),
                base64_decode($attachment['file_name']),
                ['mime' => base64_decode($attachment['mime'])]
            );
        }

        $this->checkRules('Replies');

        $this->email->with([
            'shouldBlock' => $this->size === 0,
            'encryptedParts' => $this->encryptedParts,
            'needsDkimSignature' => $this->needsDkimSignature(),
            'aliasDomain' => $this->alias->domain,
        ]);

        if ($this->alias->isCustomDomain() && ! $this->needsDkimSignature()) {
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
            'code' => 'An error has occurred, please check the logs.',
            'attempted_at' => now(),
        ]);
    }

    private function needsDkimSignature()
    {
        return $this->alias->isCustomDomain() ? $this->alias->aliasable->isVerifiedForSending() : false;
    }

    private function removeRealEmailAndTextBanner($text)
    {
        return Str::of(str_ireplace($this->sender, '', $text))
            ->replaceMatches('/(?s)((<|&lt;)!--banner-info--(&gt;|>)).*?((<|&lt;)!--banner-info--(&gt;|>))/mi', '');
    }

    private function removeRealEmailAndHtmlBanner($html)
    {
        // Reply may be HTML but have a plain text banner
        return Str::of(str_ireplace($this->sender, '', $html))
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
