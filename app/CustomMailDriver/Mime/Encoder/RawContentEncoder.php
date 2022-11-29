<?php

namespace App\CustomMailDriver\Mime\Encoder;

use Symfony\Component\Mime\Encoder\ContentEncoderInterface;

final class RawContentEncoder implements ContentEncoderInterface
{
    public function encodeByteStream($stream, int $maxLineLength = 0): iterable
    {
        while (! feof($stream)) {
            yield fread($stream, 8192);
        }
    }

    public function getName(): string
    {
        return 'raw';
    }

    public function encodeString(string $string, ?string $charset = 'utf-8', int $firstLineOffset = 0, int $maxLineLength = 0): string
    {
        return $string;
    }
}
