<?php

namespace App\CustomMailDriver\Mime\Part;

use App\CustomMailDriver\Mime\Encoder\RawContentEncoder;
use Symfony\Component\Mime\Encoder\ContentEncoderInterface;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Part\AbstractPart;

class EncryptedPart extends AbstractPart
{
    /** @internal */
    protected $_headers;

    private $body;

    private $charset;

    private $subtype;

    /**
     * @var ?string
     */
    private $disposition;

    private $seekable;

    /**
     * @param  resource|string  $body
     */
    public function __construct($body, ?string $charset = 'utf-8', string $subtype = 'plain')
    {
        unset($this->_headers);

        parent::__construct();

        if (! \is_string($body) && ! \is_resource($body)) {
            throw new \TypeError(sprintf('The body of "%s" must be a string or a resource (got "%s").', self::class, get_debug_type($body)));
        }

        $this->body = $body;
        $this->charset = $charset;
        $this->subtype = $subtype;
        $this->seekable = \is_resource($body) ? stream_get_meta_data($body)['seekable'] && 0 === fseek($body, 0, \SEEK_CUR) : null;
    }

    public function getMediaType(): string
    {
        return 'text';
    }

    public function getMediaSubtype(): string
    {
        return $this->subtype;
    }

    public function getBody(): string
    {
        if (null === $this->seekable) {
            return $this->body;
        }

        if ($this->seekable) {
            rewind($this->body);
        }

        return stream_get_contents($this->body) ?: '';
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
        return clone new Headers();
    }

    public function asDebugString(): string
    {
        $str = parent::asDebugString();
        if (null !== $this->charset) {
            $str .= ' charset: '.$this->charset;
        }
        if (null !== $this->disposition) {
            $str .= ' disposition: '.$this->disposition;
        }

        return $str;
    }

    private function getEncoder(): ContentEncoderInterface
    {
        return new RawContentEncoder();
    }

    public function __sleep(): array
    {
        // convert resources to strings for serialization
        if (null !== $this->seekable) {
            $this->body = $this->getBody();
        }

        $this->_headers = $this->getHeaders();

        return ['_headers', 'body', 'charset', 'subtype', 'disposition', 'name', 'encoding'];
    }

    public function __wakeup()
    {
        $r = new \ReflectionProperty(AbstractPart::class, 'headers');
        $r->setAccessible(true);
        $r->setValue($this, $this->_headers);
        unset($this->_headers);
    }
}
