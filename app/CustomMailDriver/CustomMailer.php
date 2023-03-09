<?php

namespace App\CustomMailDriver;

use App\CustomMailDriver\Mime\Crypto\AlreadyEncrypted;
use App\CustomMailDriver\Mime\Crypto\OpenPGPEncrypter;
use App\Models\PostfixQueueId;
use App\Models\Recipient;
use App\Notifications\GpgKeyExpired;
use Illuminate\Contracts\Mail\Mailable as MailableContract;
use Illuminate\Database\QueryException;
use Illuminate\Mail\Mailer;
use Illuminate\Mail\SentMessage;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\RuntimeException;
use Symfony\Component\Mime\Crypto\DkimOptions;
use Symfony\Component\Mime\Crypto\DkimSigner;
use Symfony\Component\Mime\Email;

class CustomMailer extends Mailer
{
    /**
     * Send a new message using a view.
     *
     * @param  MailableContract|string|array  $view
     * @param  \Closure|string|null  $callback
     * @return SentMessage|null
     */
    public function send($view, array $data = [], $callback = null)
    {
        if ($view instanceof MailableContract) {
            return $this->sendMailable($view);
        }

        // First we need to parse the view, which could either be a string or an array
        // containing both an HTML and plain text versions of the view which should
        // be used when sending an e-mail. We will extract both of them out here.
        [$view, $plain, $raw] = $this->parseView($view);

        $data['message'] = $message = $this->createMessage();

        // Once we have retrieved the view content for the e-mail we will set the body
        // of this message using the HTML type, which will provide a simple wrapper
        // to creating view based emails that are able to receive arrays of data.
        if (! is_null($callback)) {
            $callback($message);
        }

        $this->addContent($message, $view, $plain, $raw, $data);

        // If a global "to" address has been set, we will set that address on the mail
        // message. This is primarily useful during local development in which each
        // message should be delivered into a single mail address for inspection.
        if (isset($this->to['address'])) {
            $this->setGlobalToAndRemoveCcAndBcc($message);
        }

        // Next we will determine if the message should be sent. We give the developer
        // one final chance to stop this message and then we will send it to all of
        // its recipients. We will then fire the sent event for the sent message.
        $symfonyMessage = $message->getSymfonyMessage();

        // OpenPGPEncrypter
        if (isset($data['fingerprint']) && $data['fingerprint']) {
            $recipient = Recipient::find($data['recipientId']);

            try {
                $encrypter = new OpenPGPEncrypter(config('anonaddy.signing_key_fingerprint'), $data['fingerprint'], '~/.gnupg', $recipient->protected_headers);
            } catch (RuntimeException $e) {
                info($e->getMessage());
                $encrypter = null;

                $recipient->update(['should_encrypt' => false]);

                $recipient->notify(new GpgKeyExpired());
            }

            if ($encrypter) {
                $symfonyMessage = $recipient->inline_encryption ? $encrypter->encryptInline($symfonyMessage) : $encrypter->encrypt($symfonyMessage);
            }
        }

        // Already encrypted
        if (isset($data['encryptedParts']) && $data['encryptedParts']) {
            $symfonyMessage = (new AlreadyEncrypted($data['encryptedParts']))->update($symfonyMessage);
        }

        // DkimSigner only for forwards, replies and sends...
        if (isset($data['needsDkimSignature']) && $data['needsDkimSignature']) {
            $dkimSigner = new DkimSigner(config('anonaddy.dkim_signing_key'), $data['aliasDomain'], config('anonaddy.dkim_selector'));

            $options = (new DkimOptions())->headersToIgnore([
                'List-Unsubscribe',
                'Return-Path',
                'Feedback-ID',
                'Content-Type',
                'Content-Description',
                'Content-Disposition',
                'Content-Transfer-Encoding',
                'MIME-Version',
                'Alias-To',
                'X-AnonAddy-Authentication-Results',
                'X-AnonAddy-Original-Sender',
                'X-AnonAddy-Original-Envelope-From',
                'X-AnonAddy-Original-From-Header',
                'X-AnonAddy-Original-To',
                'In-Reply-To',
                'References',
                'From',
                'To',
                'Message-ID',
                'Subject',
                'Date',
                'Original-Sender',
                'Sender',
                'Received',
            ])->toArray();
            $signedEmail = $dkimSigner->sign($symfonyMessage, $options);
            $symfonyMessage->setHeaders($signedEmail->getHeaders());
        }

        if ($this->shouldSendMessage($symfonyMessage, $data)) {
            $symfonySentMessage = $this->sendSymfonyMessage($symfonyMessage);

            if ($symfonySentMessage) {
                $sentMessage = new SentMessage($symfonySentMessage);

                $this->dispatchSentEvent($sentMessage, $data);

                try {
                    // Get Postfix Queue ID and save in DB
                    $id = str_replace("\r\n", '', Str::after($sentMessage->getDebug(), 'Ok: queued as '));

                    PostfixQueueId::create([
                        'queue_id' => $id,
                    ]);
                } catch (QueryException $e) {
                    // duplicate entry
                    //Log::info('Failed to save Postfix Queue ID: ' . $id);
                }

                return $sentMessage;
            }
        }
    }

    /**
     * Send a Symfony Email instance.
     *
     * @return \Symfony\Component\Mailer\SentMessage|null
     */
    protected function sendSymfonyMessage(Email $message)
    {
        try {
            $envelopeMessage = clone $message;
            // This allows us to have the To: header set as the alias whilst still delivering to the correct RCPT TO.
            if ($aliasTo = $message->getHeaders()->get('Alias-To')) {
                $message->to($aliasTo->getValue());
                $message->getHeaders()->remove('Alias-To');
            }

            // Add the original sender header here to prevent it altering the envelope from address
            if ($originalSenderHeader = $message->getHeaders()->get('Original-Sender')) {
                $message->getHeaders()->addMailboxHeader('Sender', $originalSenderHeader->getValue());
                $message->getHeaders()->remove('Original-Sender');
            }

            return $this->transport->send($message, Envelope::create($envelopeMessage));
        } finally {
            //
        }
    }
}
