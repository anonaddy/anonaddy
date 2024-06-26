<?php

namespace App\CustomMailDriver;

use App\CustomMailDriver\Mime\Crypto\AlreadyEncrypted;
use App\CustomMailDriver\Mime\Crypto\OpenPGPEncrypter;
use App\Models\Alias;
use App\Models\OutboundMessage;
use App\Models\Recipient;
use App\Models\User;
use App\Notifications\FailedDeliveryNotification;
use App\Notifications\GpgKeyExpired;
use Exception;
use Illuminate\Contracts\Mail\Mailable as MailableContract;
use Illuminate\Mail\Mailer;
use Illuminate\Mail\SentMessage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ParagonIE\ConstantTime\Base32;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mime\Crypto\DkimOptions;
use Symfony\Component\Mime\Crypto\DkimSigner;
use Symfony\Component\Mime\Email;

class CustomMailer extends Mailer
{
    private $data;

    /**
     * Send a new message using a view.
     *
     * @param  MailableContract|string|array  $view
     * @param  \Closure|string|null  $callback
     * @return SentMessage|null
     */
    public function send($view, array $data = [], $callback = null)
    {
        $this->data = $data;

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
        if (isset($data['needsDkimSignature']) && $data['needsDkimSignature'] && ! is_null(config('anonaddy.dkim_signing_key'))) {
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
                $symfonyMessage->returnPath($verpLocalPart.'@'.$data['verpDomain']);
            } else {
                $symfonyMessage->returnPath($verpLocalPart.'@'.config('anonaddy.domain'));
            }

            try {
                $symfonySentMessage = $this->sendSymfonyMessage($symfonyMessage);
            } catch (Exception $e) {
                $symfonySentMessage = false;
                $userId = $data['userId'] ?? '';

                // Store the undelivered message if enabled by user. Do not store email verification notifications.
                if ($user = User::find($userId)) {
                    $failedDeliveryId = Uuid::uuid4();

                    // Example $e->getMessage();
                    // Expected response code "250/251/252" but got code "554", with message "554 5.7.1 Spam message rejected".
                    // Expected response code "250" but got empty code.
                    // Connection could not be established with host "mail.example:25": stream_socket_client(): Unable to connect to mail.example.com:25 (Connection refused)
                    $matches = Str::of($e->getMessage())->matchAll('/"([^"]*)"/');
                    $status = $matches[1] ?? '4.3.2';
                    $code = $matches[2] ?? '453 4.3.2 A temporary error has occurred.';

                    if ($code && $status) {
                        // If the error is temporary e.g. connection lost then rethrow the error to allow retry or send to failed_jobs table
                        if (Str::startsWith($status, '4')) {
                            throw $e;
                        }

                        // Try to determine the bounce type, HARD, SPAM, SOFT
                        $bounceType = $this->getBounceType($code, $status);

                        $diagnosticCode = Str::limit($code, 497);
                    } else {
                        $bounceType = null;
                        $diagnosticCode = null;
                    }

                    $emailType = $data['emailType'] ?? null;

                    if ($user->store_failed_deliveries && ! in_array($emailType, ['VR', 'VU'])) {
                        $isStored = Storage::disk('local')->put("{$failedDeliveryId}.eml", $symfonyMessage->toString());
                    }

                    $failedDelivery = $user->failedDeliveries()->create([
                        'id' => $failedDeliveryId,
                        'recipient_id' => $data['recipientId'] ?? null,
                        'alias_id' => $data['aliasId'] ?? null,
                        'is_stored' => $isStored ?? false,
                        'bounce_type' => $bounceType,
                        'remote_mta' => config('mail.mailers.smtp.host'),
                        'sender' => $symfonyMessage->getHeaders()->get('X-AnonAddy-Original-Sender')?->getValue(),
                        'destination' => $symfonyMessage->getTo()[0]?->getAddress(),
                        'email_type' => $emailType,
                        'status' => $status,
                        'code' => $diagnosticCode,
                        'attempted_at' => now(),
                    ]);

                    // Calling $failedDelivery->email_type will return 'Failed Delivery' and not 'FDN'
                    // Check if the bounce is a Failed delivery notification or Alias deactivated notification and if so do not notify the user again
                    if (! in_array($emailType, ['FDN', 'ADN']) && ! is_null($emailType)) {

                        $recipient = Recipient::find($failedDelivery->recipient_id);
                        $alias = Alias::find($failedDelivery->alias_id);

                        $notifiable = $recipient?->email_verified_at ? $recipient : $user?->defaultRecipient;

                        // Notify user of failed delivery
                        if ($notifiable?->email_verified_at) {

                            $notifiable->notify(new FailedDeliveryNotification($alias->email ?? null, $failedDelivery->sender, $symfonyMessage->getSubject(), $failedDelivery?->is_stored, $user?->store_failed_deliveries, $recipient?->email));
                        }
                    }
                }
            }

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

            // Add in original Tos that have been updated
            if ($tos = $this->data['tos'] ?? null) {
                foreach ($tos as $key => $to) {
                    if ($key === 0) {
                        // This allows us to have the To: header set as the alias whilst still delivering to the correct RCPT TO for forwards.
                        $message->to($to); // In order to override recipient email for forwards
                    } else {
                        $message->addTo($to);
                    }
                }
            }

            // Add in original CCs that have been updated
            if ($ccs = $this->data['ccs'] ?? null) {
                foreach ($ccs as $cc) {
                    $message->addCc($cc);
                }
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

    protected function getBounceType($code, $status)
    {
        if (preg_match("/(:?mailbox|address|user|account|recipient|@).*(:?rejected|unknown|disabled|unavailable|invalid|inactive|not exist|does(n't| not) exist)|(:?rejected|unknown|unavailable|no|illegal|invalid|no such).*(:?mailbox|address|user|account|recipient|alias)|(:?address|user|recipient) does(n't| not) have .*(:?mailbox|account)|returned to sender|(:?auth).*(:?required)/i", $code)) {

            // If the status starts with 4 then return soft instead of hard
            if (Str::startsWith($status, '4')) {
                return 'soft';
            }

            return 'hard';
        }

        if (preg_match('/(:?spam|unsolicited|blacklisting|blacklisted|blacklist|554|mail content denied|reject for policy reason|mail rejected by destination domain|security issue)/i', $code)) {
            return 'spam';
        }

        // No match for code but status starts with 5 e.g. 5.2.2
        if (Str::startsWith($status, '5')) {
            return 'hard';
        }

        return 'soft';
    }
}
