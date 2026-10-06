<?php

namespace Tests\Unit;

use App\CustomMailDriver\Mime\Part\CustomDataPart;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class CustomDataPartTest extends TestCase
{
    #[Test]
    public function it_sanitises_content_id_to_avoid_control_character_exceptions(): void
    {
        $part = new CustomDataPart('hello', 'file.txt', 'text/plain');

        $part->setContentId("<cid\r\n@example.com>");

        $headers = $part->getPreparedHeaders();

        $this->assertSame(['cid@example.com'], $headers->getHeaderBody('Content-ID'));
    }

    #[Test]
    public function it_preserves_non_addr_spec_content_id_for_inline_cid_matching(): void
    {
        $part = new CustomDataPart('hello', 'file.txt', 'text/plain');

        $part->setContentId('<7deaa9c7-507c-400a-be89-9424481eb119>');

        $headers = $part->getPreparedHeaders();

        $this->assertSame(['7deaa9c7-507c-400a-be89-9424481eb119'], $headers->getHeaderBody('Content-ID'));
    }

    #[Test]
    public function it_renders_email_without_exception_when_content_id_has_no_at_symbol(): void
    {
        $part = new CustomDataPart('hello', 'file.txt', 'text/plain');
        $part->asInline();
        $part->setContentId('7deaa9c7-507c-400a-be89-9424481eb119');

        $email = (new Email)
            ->from('sender@example.com')
            ->to('to@example.com')
            ->subject('cid test')
            ->html('<img src="cid:7deaa9c7-507c-400a-be89-9424481eb119">');
        $email->addPart($part);

        $raw = $email->toString();

        $this->assertStringContainsString('Content-ID: <7deaa9c7-507c-400a-be89-9424481eb119>', $raw);
    }

    #[Test]
    public function it_sends_attached_emails_as_eight_bit_instead_of_base64(): void
    {
        $nestedEmail = "From: sender@example.com\r\nTo: recipient@example.com\r\nSubject: Nested\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=utf-8\r\n\r\nNested body\r\n";

        $part = new CustomDataPart($nestedEmail, 'attached.eml', 'message/rfc822');

        $this->assertSame('message/rfc822', $part->getContentType());

        $raw = (new Email)
            ->from('sender@example.com')
            ->to('to@example.com')
            ->subject('rfc822 test')
            ->text('See attached')
            ->addPart($part)
            ->toString();

        $this->assertStringContainsString('Content-Type: message/rfc822', $raw);
        $this->assertStringContainsString('Content-Transfer-Encoding: 8bit', $raw);
        $this->assertStringContainsString("Subject: Nested\r\n", $raw);
        $this->assertStringNotContainsString(base64_encode($nestedEmail), $raw);
    }

    #[Test]
    public function it_downgrades_attached_emails_with_overlong_lines_to_octet_stream(): void
    {
        $nestedEmail = "From: sender@example.com\r\n\r\n".str_repeat('a', 1500)."\r\n";

        $part = new CustomDataPart($nestedEmail, 'attached.eml', 'message/rfc822');

        $this->assertSame('application/octet-stream', $part->getContentType());
    }

    #[Test]
    public function it_downgrades_attached_emails_with_bare_line_endings_to_octet_stream(): void
    {
        $bareLf = "From: sender@example.com\n\nBody\n";
        $bareCr = "From: sender@example.com\r\rBody\r";

        $this->assertSame('application/octet-stream', (new CustomDataPart($bareLf, 'a.eml', 'message/rfc822'))->getContentType());
        $this->assertSame('application/octet-stream', (new CustomDataPart($bareCr, 'b.eml', 'message/rfc822'))->getContentType());
    }

    #[Test]
    public function it_downgrades_attached_emails_with_nul_bytes_to_octet_stream(): void
    {
        $nestedEmail = "From: sender@example.com\r\n\r\nBody\0\r\n";

        $part = new CustomDataPart($nestedEmail, 'attached.eml', 'message/rfc822');

        $this->assertSame('application/octet-stream', $part->getContentType());
    }

    #[Test]
    public function it_still_encodes_regular_attachments_as_base64(): void
    {
        $part = new CustomDataPart('binary-content', 'file.pdf', 'application/pdf');

        $raw = (new Email)
            ->from('sender@example.com')
            ->to('to@example.com')
            ->subject('pdf test')
            ->text('See attached')
            ->addPart($part)
            ->toString();

        $this->assertStringContainsString('Content-Type: application/pdf', $raw);
        $this->assertStringContainsString('Content-Transfer-Encoding: base64', $raw);
    }

    #[Test]
    public function it_sanitises_content_id_with_more_than_one_at_sign(): void
    {
        $part = new CustomDataPart('hello', 'file.txt', 'text/plain');

        $part->setContentId('img1@info.support.Xq0@clippers.skillsclips.com');

        $headers = $part->getPreparedHeaders();

        $this->assertSame(['img1.info.support.Xq0@clippers.skillsclips.com'], $headers->getHeaderBody('Content-ID'));
    }

    #[Test]
    public function it_renders_email_without_exception_when_content_id_has_two_at_signs(): void
    {
        $part = new CustomDataPart('hello', 'file.txt', 'text/plain');
        $part->asInline();
        $part->setContentId('img1@info.support.Xq0@clippers.skillsclips.com');

        $html = $part->rewriteHtmlCidReferences('<img src="cid:img1@info.support.Xq0@clippers.skillsclips.com">');

        $email = (new Email)
            ->from('sender@example.com')
            ->to('to@example.com')
            ->subject('cid test')
            ->html($html);
        $email->addPart($part);

        $raw = $email->toString();

        $this->assertStringContainsString('Content-ID: <img1.info.support.Xq0@clippers.skillsclips.com>', $raw);
        $this->assertStringContainsString('cid:img1.info.support.Xq0@clippers.skillsclips.com', $raw);
    }

    #[Test]
    public function it_falls_back_to_a_generated_content_id_when_invalid(): void
    {
        $part = new CustomDataPart('hello', 'file.txt', 'text/plain');

        $part->setContentId(" \r\n\t ");

        $headers = $part->getPreparedHeaders();

        $contentIds = $headers->getHeaderBody('Content-ID');

        $this->assertIsArray($contentIds);
        $this->assertCount(1, $contentIds);
        $this->assertStringContainsString('@symfony', $contentIds[0]);
    }
}
