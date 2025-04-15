<?php

namespace App\CustomMailDriver\Mime\Part;

use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Part\DataPart;

class CustomDataPart extends DataPart
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

    public function getContentId(): string
    {
        if (! isset($this->cid)) {
            return $this->cid = $this->generateContentId();
        }

        return $this->cid ?: $this->cid = $this->generateContentId();
    }

    public function hasContentId(): bool
    {
        if (! isset($this->cid)) {
            return false;
        }

        return $this->cid !== null;
    }

    private function generateContentId(): string
    {
        return bin2hex(random_bytes(16)).'@symfony';
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

        if (isset($this->cid) && $this->cid !== null) {
            $headers->setHeaderBody('Id', 'Content-ID', $this->cid);
        }

        if (isset($this->filename) && $this->filename !== null) {
            $headers->setHeaderParameter('Content-Disposition', 'filename', $this->filename);
        }

        return $headers;
    }
}
