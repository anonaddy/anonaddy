<?php

namespace App\Mail;

use DOMDocument;

final class ForwardedEmailHtmlDocument
{
    /**
     * Return the inner HTML of the first body element so the fragment can be embedded in a wrapper
     * template without nested document roots (html/head/body).
     *
     * If the string has no body element, it is returned unchanged.
     */
    public static function innerHtmlForEmbedding(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        if (! preg_match('#<body(\s[^>]*)?>#i', $html)) {
            return $html;
        }

        // {@link https://www.w3.org/TR/html401/struct/global.html#h-7.4.4.1} Meta charset / Content-Type
        // affects how libxml interprets the byte stream. Our HTML string is always UTF-8 here (the MIME
        // parser has already decoded the part), but Outlook and others often keep charset=iso-8859-1 in
        // &lt;meta&gt; while the body is Unicode. loadHTML then mis-reads UTF-8 octets as Latin-1 and
        // saveHTML emits spurious double-encoded UTF-8 (e.g. ä as C3 83 C2 A4). Strip these hints so the
        // explicit XML encoding declaration below is the only guide.
        $html = self::stripCharsetDeclaringMetasForUtf8DomParse($html);

        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8">'.$html,
            LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT
        );
        libxml_clear_errors();

        $body = $document->getElementsByTagName('body')->item(0);
        if ($body === null) {
            return $html;
        }

        $innerHtml = '';
        foreach ($body->childNodes as $child) {
            $innerHtml .= $document->saveHTML($child);
        }

        return $innerHtml;
    }

    private static function stripCharsetDeclaringMetasForUtf8DomParse(string $html): string
    {
        $html = preg_replace(
            '/<meta\b[^>]*\bhttp-equiv\s*=\s*["\']?Content-Type["\']?[^>]*>/iu',
            '',
            $html
        ) ?? $html;

        $html = preg_replace(
            '/<meta\b[^>]*\bcharset\s*=\s*[^>\s]+[^>]*>/iu',
            '',
            $html
        ) ?? $html;

        return $html;
    }
}
