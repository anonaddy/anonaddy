<?php

namespace App\Models;

use Illuminate\Support\Str;
use PhpMimeMailParser\Parser;
use Symfony\Component\Mime\MimeTypes;

class EmailData
{
    private static $mimeTypes;

    public function __construct(Parser $parser, $sender, $size, $emailType = 'F')
    {
        if (isset($parser->getAddresses('from')[0]['address'])) {
            if (filter_var($parser->getAddresses('from')[0]['address'], FILTER_VALIDATE_EMAIL)) {
                $this->sender = $parser->getAddresses('from')[0]['address'];
            }
        }

        // If we can't get a From header then use the envelope from
        if (! isset($this->sender)) {
            $this->sender = $sender;
        }

        if (isset($parser->getAddresses('from')[0]['display'])) {
            $this->display_from = base64_encode($parser->getAddresses('from')[0]['display']);
        } else {
            $this->display_from = '';
        }

        if (isset($parser->getAddresses('reply-to')[0])) {
            if (filter_var($parser->getAddresses('reply-to')[0]['address'], FILTER_VALIDATE_EMAIL)) {
                $this->reply_to_address = $parser->getAddresses('reply-to')[0]['address'];
            }
        }

        if ($originalCc = $parser->getHeader('cc')) {
            $this->originalCc = $originalCc;
        }

        if ($originalTo = $parser->getHeader('to')) {
            $this->originalTo = $originalTo;
        }

        $this->subject = base64_encode($parser->getHeader('subject'));
        $this->text = base64_encode($parser->getMessageBody('text'));
        $this->html = base64_encode($parser->getMessageBody('html'));
        $this->attachments = [];
        $this->inlineAttachments = [];
        $this->size = $size;
        $this->messageId = base64_encode(Str::remove(['<', '>'], $parser->getHeader('Message-ID')));
        $this->listUnsubscribe = base64_encode($parser->getHeader('List-Unsubscribe'));
        $this->inReplyTo = base64_encode($parser->getHeader('In-Reply-To'));
        $this->references = base64_encode($parser->getHeader('References'));
        $this->originalEnvelopeFrom = $sender;
        $this->originalFromHeader = base64_encode($parser->getHeader('From'));
        $this->originalReplyToHeader = base64_encode($parser->getHeader('Reply-To'));
        $this->originalSenderHeader = base64_encode($parser->getHeader('Sender'));
        $this->authenticationResults = $parser->getHeader('X-AnonAddy-Authentication-Results');
        $this->receivedHeaders = $parser->getRawHeader('Received');

        $isReplyOrSend = in_array($emailType, ['R', 'S']);

        if ($parser->getParts()[1]['content-type'] === 'multipart/encrypted') {
            $this->encryptedParts = $parser->getAttachments();

            // Only try to decrypt Replies or Sends from aliases
            if ($isReplyOrSend && config('anonaddy.signing_key_fingerprint')) {

                // Check if encrypted with addy.io public key and needs decrypting
                $part = collect($this->encryptedParts)->filter(function ($part) {
                    return $part->getContentType() === 'application/octet-stream';
                })->first();

                if ($part) {
                    $this->attemptToDecrypt($part);
                }

            }
        } else {
            // If this is a reply or send from an alias then remove any public keys
            $this->addAttachments($parser, $isReplyOrSend, $isReplyOrSend);
        }

        if (preg_match('/^-----BEGIN PGP MESSAGE-----([A-Za-z0-9+=\/\n]+)-----END PGP MESSAGE-----$/', $parser->getMessageBody('text'))) {
            $this->isInlineEncrypted = true;

            if ($isReplyOrSend && config('anonaddy.signing_key_fingerprint')) {
                $this->attemptToDecryptInline($parser->getMessageBody('text'));
            }
        }
    }

    private function addAttachments(Parser $parser, $removePublicKeys = false, $removeSignature = false)
    {
        foreach ($parser->getAttachments() as $attachment) {
            // Fix incorrect Content Types e.g. 'png', 'pdf', '.pdf', 'text'
            $contentType = $attachment->getContentType();

            if ($removePublicKeys && $contentType === 'application/pgp-keys') {
                continue;
            }

            if ($removeSignature && $contentType === 'application/pgp-signature') {
                continue;
            }

            if ($contentType === 'text') {
                $this->text = base64_encode(stream_get_contents($attachment->getStream()));
            } else {
                if (! str_contains($contentType, '/')) {
                    if (self::$mimeTypes === null) {
                        self::$mimeTypes = new MimeTypes();
                    }
                    $contentType = self::$mimeTypes->getMimeTypes($contentType)[0] ?? 'application/octet-stream';
                }

                if ($attachment->getContentDisposition() === 'inline') {
                    $this->inlineAttachments[] = [
                        'stream' => base64_encode(stream_get_contents($attachment->getStream())),
                        'file_name' => base64_encode($attachment->getFileName()),
                        'mime' => base64_encode($contentType),
                        'contentDisposition' => base64_encode($attachment->getContentDisposition()),
                        'contentId' => base64_encode($attachment->getContentID()),
                    ];
                } else {
                    $this->attachments[] = [
                        'stream' => base64_encode(stream_get_contents($attachment->getStream())),
                        'file_name' => base64_encode($attachment->getFileName()),
                        'mime' => base64_encode($contentType),
                    ];
                }
            }
        }
    }

    private function attemptToDecrypt($part)
    {
        try {
            $gnupg = new \gnupg();

            $gnupg->cleardecryptkeys();
            $gnupg->adddecryptkey(config('anonaddy.signing_key_fingerprint'), null);

            $encrypted = stream_get_contents($part->getStream());

            $decrypted = $gnupg->decrypt($encrypted);

            if ($decrypted) {

                $decryptedParser = new Parser();
                $decryptedParser->setText($decrypted);

                // Set decrypted data as subject (as may have encrypted subject too), html and text
                $this->subject = base64_encode($decryptedParser->getHeader('subject'));
                $this->text = base64_encode($decryptedParser->getMessageBody('text'));
                $this->html = base64_encode($decryptedParser->getMessageBody('html'));
                // Add attachments
                $this->addAttachments($decryptedParser, true, true);

                // Set encrypted parts to NULL
                $this->encryptedParts = null;
            }
        } catch (\Exception $e) {
            report($e);
        }
    }

    private function attemptToDecryptInline($text)
    {
        try {
            $gnupg = new \gnupg();

            $gnupg->cleardecryptkeys();
            $gnupg->adddecryptkey(config('anonaddy.signing_key_fingerprint'), null);

            $decrypted = $gnupg->decrypt($text);

            if ($decrypted) {
                // Set decrypted text as message text
                $this->text = base64_encode($decrypted);
                $this->html = null;
            }
        } catch (\Exception $e) {
            report($e);
        }
    }
}
