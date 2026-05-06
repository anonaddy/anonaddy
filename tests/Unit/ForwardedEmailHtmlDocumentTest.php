<?php

namespace Tests\Unit;

use App\Mail\ForwardedEmailHtmlDocument;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ForwardedEmailHtmlDocumentTest extends TestCase
{
    #[Test]
    public function empty_string_is_unchanged(): void
    {
        $this->assertSame('', ForwardedEmailHtmlDocument::innerHtmlForEmbedding(''));
    }

    #[Test]
    public function html_fragment_without_body_tag_is_unchanged(): void
    {
        $html = '<table><tr><td>Hello</td></tr></table>';

        $this->assertSame($html, ForwardedEmailHtmlDocument::innerHtmlForEmbedding($html));
    }

    #[Test]
    public function tbody_does_not_trigger_body_extraction(): void
    {
        $html = '<table><tbody><tr><td>x</td></tr></tbody></table>';

        $this->assertSame($html, ForwardedEmailHtmlDocument::innerHtmlForEmbedding($html));
    }

    #[Test]
    public function full_document_inner_content_is_returned_without_wrappers(): void
    {
        $html = '<!DOCTYPE html><html><head><title>t</title></head><body><p>Hi</p><table><tr><td>Cell</td></tr></table></body></html>';

        $result = ForwardedEmailHtmlDocument::innerHtmlForEmbedding($html);

        $this->assertStringContainsString('<p>Hi</p>', $result);
        $this->assertStringContainsString('<table>', $result);
        $this->assertStringNotContainsString('<html', strtolower($result));
        $this->assertStringNotContainsString('<head', strtolower($result));
        $this->assertStringNotContainsString('<body', strtolower($result));
    }

    #[Test]
    public function utf8_content_in_body_is_preserved(): void
    {
        $html = '<html><head><meta charset="UTF-8"></head><body><p>Café 日本語</p></body></html>';

        $result = ForwardedEmailHtmlDocument::innerHtmlForEmbedding($html);

        $this->assertStringContainsString('Café 日本語', $result);
    }

    #[Test]
    public function misleading_iso_8859_1_charset_meta_does_not_double_encode_utf8_body(): void
    {
        // Common from Outlook after php-mime-mail-parser: decoded body is UTF-8 but head still declares latin-1.
        $html = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"></head>'
            .'<body><div>'."\xC3\xA4\xC3\xB6\xC3\xBC\xC3\x9F".'</div></body></html>';

        $result = ForwardedEmailHtmlDocument::innerHtmlForEmbedding($html);

        $this->assertStringContainsString('äöüß', $result);
        $this->assertStringNotContainsString("\xC3\x83\xC2\xA4", $result);
    }

    #[Test]
    public function body_with_only_text_whitespace_is_extracted(): void
    {
        $html = '<html><head></head><body>   </body></html>';

        $result = ForwardedEmailHtmlDocument::innerHtmlForEmbedding($html);

        $this->assertMatchesRegularExpression('/^\s*$/', $result);
    }
}
