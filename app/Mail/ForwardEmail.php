<?php

namespace App\Mail;

use App\Alias;
use App\EmailData;
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
    protected $emailSubject;
    protected $emailText;
    protected $emailHtml;
    protected $emailAttachments;
    protected $deactivateUrl;
    protected $bannerLocation;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Alias $alias, EmailData $emailData)
    {
        $this->alias = $alias;
        $this->sender = $emailData->sender;
        $this->displayFrom = $emailData->display_from;
        $this->emailSubject = $emailData->subject;
        $this->emailText = $emailData->text;
        $this->emailHtml = $emailData->html;
        $this->emailAttachments = $emailData->attachments;

        $this->deactivateUrl = URL::signedRoute('deactivate', ['alias' => $alias->id]);
        $this->bannerLocation = $this->alias->user->banner_location;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $replyToEmail = $this->alias->local_part.'+'.sha1(config('anonaddy.secret').$this->sender).'@'.$this->alias->domain;

        $email =  $this
            ->from(config('mail.from.address'), $this->displayFrom." '".$this->sender."' via ".config('app.name'))
            ->replyTo($replyToEmail, $this->sender)
            ->subject($this->emailSubject)
            ->text('emails.forward.text')->with([
                'text' => $this->emailText
            ])
            ->with([
                'location' => $this->bannerLocation,
                'deactivateUrl' => $this->deactivateUrl,
                'aliasEmail' => $this->alias->email,
                'fromEmail' => $this->sender
            ])
            ->withSwiftMessage(function ($message) {
                $message->getHeaders()
                        ->addTextHeader('List-Unsubscribe', '<' . $this->deactivateUrl . '>, <mailto:' . $this->alias->id . '@unsubscribe.' . config('anonaddy.domain') . '>');

                $message->getHeaders()
                        ->addTextHeader('Return-Path', 'bounces@' . config('anonaddy.domain'));
            });

        if ($this->emailHtml) {
            $email->view('emails.forward.html')->with([
                'html' => $this->emailHtml
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
