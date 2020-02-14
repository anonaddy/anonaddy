<?php

namespace App\Helpers;

use Swift_DependencyContainer;
use Swift_Message;
use Swift_Signers_BodySigner;
use Swift_SwiftException;

class AlreadyEncryptedSigner implements Swift_Signers_BodySigner
{
    protected $attachments;

    public function __construct($attachments)
    {
        $this->attachments = $attachments;
    }

    /**
     * @param Swift_Message $message
     *
     * @return $this
     *
     * @throws Swift_DependencyException
     * @throws Swift_SwiftException
     */
    public function signMessage(Swift_Message $message)
    {
        $message->setChildren([]);

        $message->setEncoder(Swift_DependencyContainer::getInstance()->lookup('mime.rawcontentencoder'));

        $type = $message->getHeaders()->get('Content-Type');

        $type->setValue('multipart/encrypted');

        $type->setParameters([
            'protocol' => 'application/pgp-encrypted',
            'boundary' => $message->getBoundary()
        ]);

        $body = 'This is an OpenPGP/MIME encrypted message (RFC 4880 and 3156)' . PHP_EOL;

        foreach ($this->attachments as $attachment) {
            $body .= '--' . $message->getBoundary() . PHP_EOL;
            $body .= $attachment->getMimePartStr() . PHP_EOL;
        }

        $body .= '--'. $message->getBoundary() . '--';

        $message->setBody($body);

        $messageHeaders = $message->getHeaders();
        $messageHeaders->removeAll('Content-Transfer-Encoding');

        return $this;
    }

    /**
     * @return array
     */
    public function getAlteredHeaders()
    {
        return ['Content-Type', 'Content-Transfer-Encoding', 'Content-Disposition', 'Content-Description'];
    }

    /**
     * @return $this
     */
    public function reset()
    {
        return $this;
    }
}
