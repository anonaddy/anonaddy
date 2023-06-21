<?php

namespace App\CustomMailDriver\Mime\Part;

use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Part\DataPart;

class InlineImagePart extends DataPart
{
    /**
     * Sets the content-id of the file.
     *
     * @return $this
     */
    public function setContentId(string $cid): static
    {
        $this->cid = $cid;

        return $this;
    }

    /**
     * Sets the name of the file.
     *
     * @return $this
     */
    public function setFileName(string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    public function getPreparedHeaders(): Headers
    {
        $headers = parent::getPreparedHeaders();

        if (null !== $this->cid) {
            $headers->setHeaderBody('Id', 'Content-ID', $this->cid);
        }

        if (null !== $this->filename) {
            $headers->setHeaderParameter('Content-Disposition', 'filename', $this->filename);
        }

        return $headers;
    }
}
