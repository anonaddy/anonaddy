<?php

namespace App\CustomMailDriver\Mime\Part;

use Symfony\Component\Mime\Exception\RfcComplianceException;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;

class CustomDataPart extends DataPart
{
    /**
     * @var int
     */
    private const MAX_LINE_LENGTH = 998;

    private ?string $cid;

    private ?string $requestedCid = null;

    private ?string $filename;

    /**
     * RFC 2046 only permits 7bit, 8bit or binary transfer encodings for message/*
     * parts, but Symfony defaults DataPart to base64. Exchange Online then tries to
     * parse the base64 lines as the nested message's headers and rejects large
     * attached emails with "554 5.6.211 Invalid MIME Content: Number of Header
     * objects exceeded". Send message/* verbatim as 8bit when safe, otherwise
     * downgrade to application/octet-stream so it is treated as an opaque file.
     *
     * @param  resource|string|File  $body
     */
    public function __construct($body, ?string $filename = null, ?string $contentType = null, ?string $encoding = null)
    {
        if ($encoding === null && is_string($body) && $contentType !== null && str_starts_with(strtolower($contentType), 'message/')) {
            if (self::isSafeForEightBitTransfer($body)) {
                $encoding = '8bit';
            } else {
                $contentType = 'application/octet-stream';
            }
        }

        parent::__construct($body, $filename, $contentType, $encoding);
    }

    /**
     * The body must have CRLF line endings, no NUL bytes and no line longer than
     * RFC 5322 allows, otherwise it cannot be passed through to SMTP verbatim.
     */
    private static function isSafeForEightBitTransfer(string $body): bool
    {
        if (str_contains($body, "\0")) {
            return false;
        }

        // Bare LF or bare CR would corrupt the rendered message
        if (preg_match('/(?<!\r)\n|\r(?!\n)/', $body)) {
            return false;
        }

        return ! preg_match('/[^\r\n]{'.(self::MAX_LINE_LENGTH + 1).'}/', $body);
    }

    /**
     * Sets the content-id of the file.
     *
     * @return $this
     */
    public function setContentId(string $cid): static
    {
        $cid = trim($cid);

        // Normalise common wrappers and remove any control characters to avoid
        // Symfony Mime throwing when it later renders Identification headers.
        $cid = trim($cid, "<> \t\n\r\0\x0B");
        $cid = preg_replace('/[\x00-\x1F\x7F]/', '', $cid) ?? '';

        if ($cid === '') {
            $this->requestedCid = null;
            $this->cid = $this->generateContentId();

            return $this;
        }

        $this->requestedCid = $cid;
        $this->cid = $this->sanitiseContentId($cid);

        return $this;
    }

    public function rewriteHtmlCidReferences(string $html): string
    {
        $from = $this->requestedCid;
        $to = $this->cid ?? null;

        if ($from === null || $from === '' || $to === null || $from === $to) {
            return $html;
        }

        return str_ireplace('cid:'.$from, 'cid:'.$to, $html);
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

    /**
     * Symfony IdentificationHeader treats Content-ID as an addr-spec and rejects
     * more than one unquoted @ (for example img1@host@domain).
     */
    private function sanitiseContentId(string $cid): string
    {
        if (substr_count($cid, '@') < 2) {
            return $cid;
        }

        $lastAt = strrpos($cid, '@');
        $local = str_replace('@', '.', substr($cid, 0, $lastAt));
        $domain = substr($cid, $lastAt + 1);

        if ($local === '' || $domain === '') {
            return $this->generateContentId();
        }

        return $local.'@'.$domain;
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
            try {
                $headers->setHeaderBody('Id', 'Content-ID', $this->cid);
            } catch (RfcComplianceException) {
                $this->cid = $this->generateContentId();
                $headers->setHeaderBody('Id', 'Content-ID', $this->cid);
            }
        }

        if (isset($this->filename) && $this->filename !== null) {
            $headers->setHeaderParameter('Content-Disposition', 'filename', $this->filename);
        }

        return $headers;
    }
}
