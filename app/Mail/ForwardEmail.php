<?php

namespace App\Mail;

use App\Alias;
use App\EmailData;
use App\Helpers\OpenPGPSigner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class ForwardEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

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

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Alias $alias, EmailData $emailData, $fingerprint = null)
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
        $this->bannerLocation = $this->alias->user->banner_location;

        if ($this->fingerprint = $fingerprint) {
            $this->openpgpsigner = OpenPGPSigner::newInstance(config('anonaddy.signing_key_fingerprint'), [], "~/.gnupg");
            $this->openpgpsigner->addRecipient($fingerprint);
        }
    }

    /**
     * Build the message.4
     *
     * @return $this
     */
    public function build()
    {
        $replyToDisplay = $this->replyToAddress ?? $this->sender;

        $replyToEmail = $this->alias->local_part.'+'.sha1(config('anonaddy.secret').$replyToDisplay).'@'.$this->alias->domain;

        $email =  $this
            ->from(config('mail.from.address'), base64_decode($this->displayFrom)." '".$this->sender."' via ".config('app.name'))
            ->replyTo($replyToEmail, $replyToDisplay)
            ->subject($this->user->email_subject ?? base64_decode($this->emailSubject))
            ->text('emails.forward.text')->with([
                'text' => base64_decode($this->emailText)
            ])
            ->with([
                'location' => $this->bannerLocation,
                'deactivateUrl' => $this->deactivateUrl,
                'aliasEmail' => $this->alias->email,
                'fromEmail' => $this->sender,
                'replacedSubject' => $this->user->email_subject ? ' with subject "' . base64_decode($this->emailSubject) . '"' : null
            ])
            ->withSwiftMessage(function ($message) {
                $message->getHeaders()
                        ->addTextHeader('List-Unsubscribe', '<' . $this->deactivateUrl . '>, <mailto:' . $this->alias->id . '@unsubscribe.' . config('anonaddy.domain') . '>');

                $message->getHeaders()
                        ->addTextHeader('Return-Path', config('anonaddy.return_path'));

                if ($this->fingerprint) {
                    $message->attachSigner($this->openpgpsigner);
                }
            });

        if ($this->emailHtml) {
            $email->view('emails.forward.html')->with([
                'html' => base64_decode($this->emailHtml)
            ]);
        }

        foreach ($this->emailAttachments as $attachment) {
            $email->attachData(
                base64_decode($attachment['stream']),
                $attachment['file_name'],
                ['mime' => $attachment['mime']]
            );
        }

        return $email;
    }
}
