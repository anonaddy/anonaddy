<?php

namespace App;

use PhpMimeMailParser\Parser;

class EmailData
{
    public function __construct(Parser $parser)
    {
        $this->sender = $parser->getAddresses('from')[0]['address'];
        $this->display_from = $parser->getAddresses('from')[0]['display'];
        $this->subject = $parser->getHeader('subject');
        $this->text = $parser->getMessageBody('text');
        $this->html = $parser->getMessageBody('html');
        $this->attachments = [];

        foreach ($parser->getAttachments() as $attachment) {
            $this->attachments[] = [
              'stream' => base64_encode(stream_get_contents($attachment->getStream())),
              'file_name' => $attachment->getFileName(),
              'mime' => $attachment->getContentType()
          ];
        }
    }
}
