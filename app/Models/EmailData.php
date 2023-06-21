<?php

namespace App\Models;

use Illuminate\Support\Str;
use PhpMimeMailParser\Parser;
use Symfony\Component\Mime\MimeTypes;

class EmailData
{
    private static $mimeTypes;

    public function __construct(Parser $parser, $sender, $size)
    {
        if (isset($parser->getAddresses('from')[0]['address'])) {
            if (filter_var($parser->getAddresses('from')[0]['address'], FILTER_VALIDATE_EMAIL)) {
                $this->sender = $parser->getAddresses('from')[0]['address'];
            }
        }

        // If we can't get a From header address then use the envelope from
        if (! isset($this->sender)) {
            $this->sender = $sender;
        }

        $this->display_from = base64_encode($parser->getAddresses('from')[0]['display']);
        if (isset($parser->getAddresses('reply-to')[0])) {
            $this->reply_to_address = $parser->getAddresses('reply-to')[0]['address'];
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

        if ($parser->getParts()[1]['content-type'] === 'multipart/encrypted') {
            $this->encryptedParts = $parser->getAttachments();
        } else {
            foreach ($parser->getAttachments() as $attachment) {
                // Fix incorrect Content Types e.g. 'png', 'pdf', '.pdf', 'text'
                $contentType = $attachment->getContentType();

                if ($contentType === 'text') {
                    $this->text = base64_encode(stream_get_contents($attachment->getStream()));
                } else {
                    if (! str_contains($contentType, '/')) {
                        if (null === self::$mimeTypes) {
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
    }
}
