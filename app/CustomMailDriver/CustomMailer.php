<?php

namespace App\CustomMailDriver;

use App\CustomMailDriver\Mime\Crypto\AlreadyEncrypted;
use App\CustomMailDriver\Mime\Crypto\OpenPGPEncrypter;
use App\Models\OutboundMessage;
use App\Models\Recipient;
use App\Notifications\GpgKeyExpired;
use Exception;
use Illuminate\Contracts\Mail\Mailable as MailableContract;
use Illuminate\Mail\Mailer;
use Illuminate\Mail\SentMessage;
use ParagonIE\ConstantTime\Base32;
use Symfony\Component\Mailer\Envelope;
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

                $encryptedSymfonyMessage = $recipient->inline_encryption ? $encrypter->encryptInline($symfonyMessage) : $encrypter->encrypt($symfonyMessage);
            } catch (Exception $e) {
                info($e->getMessage());
                $encryptedSymfonyMessage = null;

                $recipient->update(['should_encrypt' => false]);

                $recipient->notify(new GpgKeyExpired());
            }

            if ($encryptedSymfonyMessage) {
                $symfonyMessage = $encryptedSymfonyMessage;
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
            // Set VERP address
            $id = randomString(12);
            $verpLocalPart = $this->getVerpLocalPart($id);

            // If the message is a forward, reply or send then use the verp domain
            if (isset($data['emailType']) && in_array($data['emailType'], ['F', 'R', 'S'])) {
                $message->returnPath($verpLocalPart.'@'.$data['verpDomain']);
            } else {
                $message->returnPath($verpLocalPart.'@'.config('anonaddy.domain'));
            }

            $symfonySentMessage = $this->sendSymfonyMessage($symfonyMessage);

            if ($symfonySentMessage) {
                $sentMessage = new SentMessage($symfonySentMessage);

                $this->dispatchSentEvent($sentMessage, $data);

                // Create a new Outbound Message for verifying any bounces
                if (isset($data['userId']) && ! is_null($data['userId']) && isset($data['emailType']) && ! is_null($data['emailType'])) {

                    try {
                        OutboundMessage::create([
                            'id' => $id,
                            'user_id' => $data['userId'],
                            'alias_id' => $data['aliasId'] ?? null,
                            'recipient_id' => $data['recipientId'] ?? null,
                            'email_type' => $data['emailType'],
                        ]);
                    } catch (Exception $e) {
                        report($e);
                    }
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

    protected function getVerpLocalPart($id)
    {
        $hmac = hash_hmac('sha3-224', $id, config('anonaddy.secret'));
        $hmacPayload = substr($hmac, 0, 8);
        $encodedPayload = Base32::encodeUnpadded($id);
        $encodedSignature = Base32::encodeUnpadded($hmacPayload);

        return "b_{$encodedPayload}_{$encodedSignature}";
    }
}
