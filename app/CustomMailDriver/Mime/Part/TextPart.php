<?php

namespace App\CustomMailDriver\Mime\Part;

use Symfony\Component\Mime\Exception\InvalidArgumentException;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Part\AbstractPart;

class TextPart extends AbstractPart
{
    /** @internal */
    protected $_headers;

    private static $encoders = [];

    private $body;
    private $boundary;
    private $charset;
    private $subtype;
    /**
     * @var ?string
     */
    private $disposition;
    private $name;
    private $encoding;
    private $seekable;

    /**
     * @param resource|string $body
     */
    public function __construct($body, ?string $boundary, ?string $charset = 'utf-8', string $subtype = 'plain', string $encoding = null)
    {
        unset($this->_headers);

        parent::__construct();

        if (!\is_string($body) && !\is_resource($body)) {
            throw new \TypeError(sprintf('The body of "%s" must be a string or a resource (got "%s").', self::class, get_debug_type($body)));
        }

        $this->body = $body;
        $this->boundary = $boundary;
        $this->charset = $charset;
        $this->subtype = $subtype;
        $this->seekable = \is_resource($body) ? stream_get_meta_data($body)['seekable'] && 0 === fseek($body, 0, \SEEK_CUR) : null;

        if (null === $encoding) {
            $this->encoding = $this->chooseEncoding();
        } else {
            if ('quoted-printable' !== $encoding && 'base64' !== $encoding && '8bit' !== $encoding) {
                throw new InvalidArgumentException(sprintf('The encoding must be one of "quoted-printable", "base64", or "8bit" ("%s" given).', $encoding));
            }
            $this->encoding = $encoding;
        }
    }

    public function getMediaType(): string
    {
        return 'text';
    }

    public function getMediaSubtype(): string
    {
        return $this->subtype;
    }

    public function bodyToString(): string
    {
        return $this->getEncoder()->encodeString($this->getBody(), $this->charset);
    }

    public function bodyToIterable(): iterable
    {
        if (null !== $this->seekable) {
            if ($this->seekable) {
                rewind($this->body);
            }
            yield from $this->getEncoder()->encodeByteStream($this->body);
        } else {
            yield $this->getEncoder()->encodeString($this->body);
        }
    }

    public function getPreparedHeaders(): Headers
    {
        $headers = parent::getPreparedHeaders();

        $headers->setHeaderBody('Parameterized', 'Content-Type', $this->getMediaType().'/'.$this->getMediaSubtype());
        if ($this->charset) {
            $headers->setHeaderParameter('Content-Type', 'charset', $this->charset);
        }
        if ($this->name && 'form-data' !== $this->disposition) {
            $headers->setHeaderParameter('Content-Type', 'name', $this->name);
        }
        $headers->setHeaderBody('Text', 'Content-Transfer-Encoding', $this->encoding);

        if (!$headers->has('Content-Disposition') && null !== $this->disposition) {
            $headers->setHeaderBody('Parameterized', 'Content-Disposition', $this->disposition);
            if ($this->name) {
                $headers->setHeaderParameter('Content-Disposition', 'name', $this->name);
            }
        }

        return $headers;
    }
}
