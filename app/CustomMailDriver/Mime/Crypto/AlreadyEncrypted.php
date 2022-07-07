<?php

namespace App\CustomMailDriver\Mime\Crypto;

use App\CustomMailDriver\Mime\Part\EncryptedPart;
use Symfony\Component\Mime\Email;

class AlreadyEncrypted
{
    protected $encryptedParts;

    public function __construct($encryptedParts)
    {
        $this->encryptedParts = $encryptedParts;
    }

    public function update(Email $message): Email
    {
        $boundary = strtr(base64_encode(random_bytes(6)), '+/', '-_');

        $headers = $message->getPreparedHeaders();

        $headers->setHeaderBody('Parameterized', 'Content-Type', 'multipart/encrypted');
        $headers->setHeaderParameter('Content-Type', 'protocol', 'application/pgp-encrypted');
        $headers->setHeaderParameter('Content-Type', 'boundary', $boundary);

        $message->setHeaders($headers);

        $body = "This is an OpenPGP/MIME encrypted message (RFC 4880 and 3156)\r\n\r\n";

        foreach ($this->encryptedParts as $part) {
            $body .= "--{$boundary}\r\n";
            $body .= $part->getMimePartStr()."\r\n";
        }

        $body .= "--{$boundary}--";

        return $message->setBody(new EncryptedPart($body));
    }
}
