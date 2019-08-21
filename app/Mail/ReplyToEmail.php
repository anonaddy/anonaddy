<?php

namespace App\Mail;

use App\Alias;
use App\EmailData;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReplyToEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $user;
    protected $alias;
    protected $emailSubject;
    protected $emailText;
    protected $emailHtml;
    protected $emailAttachments;

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
        $fromName = $this->user->from_name ? $this->user->from_name : $this->alias->email.' via '.config('app.name');

        $email =  $this
            ->from(config('mail.from.address'), $fromName)
            ->replyTo($this->alias->email, $fromName)
            ->subject($this->emailSubject)
            ->text('emails.reply.text')->with([
                'text' => base64_decode($this->emailText)
            ])
            ->withSwiftMessage(function ($message) {
                $message->getHeaders()
                        ->addTextHeader('Return-Path', config('anonaddy.return_path'));
            });

        if ($this->emailHtml) {
            $email->view('emails.reply.html')->with([
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
