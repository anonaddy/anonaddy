<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ForwardHtmlBannerTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function bannerViewData(array $overrides = []): array
    {
        return array_merge([
            'locationHtml' => 'top',
            'showSpamBanner' => false,
            'html' => '<style type="text/css">a { color: #fff !important; }</style><p>Hello <a href="https://example.com">newsletter</a></p>',
            'aliasEmail' => 'alias@example.com',
            'aliasDescription' => null,
            'fromEmail' => 'sender@example.com',
            'replacedSubject' => null,
            'deactivateUrl' => 'https://app.addy.io/deactivate/test',
            'blockEmailUrl' => null,
            'blockDomainUrl' => null,
        ], $overrides);
    }

    #[Test]
    public function banner_has_an_id_and_class_so_reset_rules_can_target_it(): void
    {
        $view = $this->view('emails.forward.html', $this->bannerViewData());

        $view->assertSee('id="addy-banner"', false);
        $view->assertSee('class="addy-banner"', false);
        $view->assertSee('class="addy-banner-link"', false);
        $view->assertSee('class="addy-banner-link-text"', false);
        $view->assertSee('https://app.addy.io/deactivate/test', false);
    }

    #[Test]
    public function banner_reset_stylesheet_comes_after_original_message_css(): void
    {
        $html = (string) $this->view('emails.forward.html', $this->bannerViewData());

        $originalStylePosition = strpos($html, 'a { color: #fff !important; }');
        $resetLinkPosition = strpos($html, '#addy-banner a:link');

        $this->assertNotFalse($originalStylePosition);
        $this->assertNotFalse($resetLinkPosition);
        $this->assertGreaterThan($originalStylePosition, $resetLinkPosition);
        $this->assertStringContainsString('color: #2d3a8c !important', $html);
        $this->assertStringContainsString('#addy-banner a:visited', $html);
        $this->assertStringContainsString('#addy-banner a:hover', $html);
        $this->assertStringContainsString('#addy-banner a:active', $html);
        $this->assertStringContainsString('a.addy-banner-link:link', $html);
        $this->assertStringContainsString('#addy-banner .addy-banner-link-text', $html);
        $this->assertStringContainsString('word-spacing: normal', $html);
        $this->assertStringContainsString('letter-spacing: normal', $html);
    }

    #[Test]
    public function bottom_banner_still_includes_the_reset_after_original_css(): void
    {
        $html = (string) $this->view('emails.forward.html', $this->bannerViewData([
            'locationHtml' => 'bottom',
        ]));

        $originalStylePosition = strpos($html, 'a { color: #fff !important; }');
        $resetLinkPosition = strpos($html, '#addy-banner a:link');

        $this->assertNotFalse($originalStylePosition);
        $this->assertNotFalse($resetLinkPosition);
        $this->assertGreaterThan($originalStylePosition, $resetLinkPosition);
        $this->assertStringContainsString('id="addy-banner"', $html);
    }

    #[Test]
    public function deactivate_link_text_is_wrapped_in_a_span_with_inline_colour(): void
    {
        $html = (string) $this->view('emails.forward.html', $this->bannerViewData());

        $this->assertMatchesRegularExpression(
            '/<a href="https:\/\/app\.addy\.io\/deactivate\/test" class="addy-banner-link"[^>]*>\s*<span class="addy-banner-link-text" style="color:#2d3a8c !important;text-decoration:underline !important;">Deactivate<\/span>\s*<\/a>/',
            $html
        );
        $this->assertStringNotContainsString('Click here', $html);

        $generalSpanColourPosition = strpos($html, '#addy-banner span');
        $linkTextColourPosition = strpos($html, '#addy-banner .addy-banner-link-text');
        $labelColourPosition = strpos($html, '#addy-banner .addy-banner-label');

        $this->assertNotFalse($generalSpanColourPosition);
        $this->assertNotFalse($labelColourPosition);
        $this->assertNotFalse($linkTextColourPosition);
        $this->assertGreaterThan($generalSpanColourPosition, $labelColourPosition);
        $this->assertGreaterThan($labelColourPosition, $linkTextColourPosition);
    }

    #[Test]
    public function original_newsletter_links_are_not_given_the_banner_link_class(): void
    {
        $html = (string) $this->view('emails.forward.html', $this->bannerViewData());

        $this->assertMatchesRegularExpression(
            '/<a href="https:\/\/app\.addy\.io\/deactivate\/test" class="addy-banner-link"/',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<a href="https:\/\/example\.com"[^>]*class="addy-banner-link"/',
            $html
        );
    }

    #[Test]
    public function banner_reset_is_omitted_when_the_html_banner_is_off(): void
    {
        $view = $this->view('emails.forward.html', $this->bannerViewData([
            'locationHtml' => 'off',
        ]));

        $view->assertDontSee('addy-banner');
        $view->assertDontSee('addy-banner-link');
        $view->assertSee('newsletter');
    }

    #[Test]
    public function banner_is_left_aligned_and_puts_description_in_brackets_after_the_alias(): void
    {
        $html = (string) $this->view('emails.forward.html', $this->bannerViewData([
            'aliasDescription' => 'Registered at Fantia',
            'replacedSubject' => 'Hello',
        ]));

        $this->assertStringContainsString('text-align:left', $html);
        $this->assertStringContainsString('font-size:13px', $html);
        $this->assertStringContainsString('word-spacing:normal', $html);
        $this->assertStringContainsString('class="addy-banner-label"', $html);
        $this->assertStringContainsString('color:#7b8794 !important;">To:</span>&nbsp;', $html);
        $this->assertStringNotContainsString('This email was sent to', $html);
        $this->assertStringContainsString('color:#7b8794 !important;">From:</span>&nbsp;', $html);
        $this->assertStringContainsString('sender@example.com', $html);
        $this->assertStringContainsString('alias@example.com</span>&nbsp;(Registered at Fantia)', $html);
        $this->assertStringNotContainsString('Registered&nbsp;at&nbsp;Fantia', $html);
        $this->assertStringNotContainsString('Description:', $html);
        $this->assertStringContainsString('color:#7b8794 !important;">Original&nbsp;subject:</span>&nbsp;', $html);
        $this->assertStringContainsString('Hello</span>', $html);
        $this->assertStringNotContainsString('with subject', $html);
        $this->assertStringContainsString('color:#7b8794 !important;">Actions:</span>&nbsp;', $html);
        $this->assertStringContainsString('Deactivate', $html);
        $this->assertStringNotContainsString('Block&nbsp;email', $html);
        $this->assertStringNotContainsString('Block domain', $html);
        $this->assertStringNotContainsString('Alias actions', $html);
    }

    #[Test]
    public function block_links_are_in_the_same_row_as_deactivate(): void
    {
        $html = (string) $this->view('emails.forward.html', $this->bannerViewData([
            'blockEmailUrl' => 'https://app.addy.io/aliases/test/actions?email=sender%40example.com&action=block_email&signature=abc',
            'blockDomainUrl' => 'https://app.addy.io/aliases/test/actions?email=sender%40example.com&action=block_domain&signature=def',
        ]));

        $this->assertMatchesRegularExpression(
            '/<tr>\s*<td>.*?Actions:<\/span>&nbsp;.*?deactivate\/test.*?Block&nbsp;email.*?Block&nbsp;domain.*?<\/td>\s*<\/tr>/s',
            $html
        );
        $this->assertStringContainsString('action=block_email', $html);
        $this->assertStringContainsString('action=block_domain', $html);
        $this->assertStringNotContainsString('Alias actions', $html);
        $this->assertStringNotContainsString('Delete', $html);
    }

    #[Test]
    public function block_domain_link_is_omitted_when_no_domain_url_is_passed(): void
    {
        $html = (string) $this->view('emails.forward.html', $this->bannerViewData([
            'blockEmailUrl' => 'https://app.addy.io/aliases/test/actions?action=block_email&signature=abc',
        ]));

        $this->assertStringContainsString('Block&nbsp;email', $html);
        $this->assertStringNotContainsString('Block&nbsp;domain', $html);
    }

    #[Test]
    public function description_html_is_escaped(): void
    {
        $html = (string) $this->view('emails.forward.html', $this->bannerViewData([
            'aliasDescription' => '<script>alert(1)</script>',
        ]));

        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
    }

    #[Test]
    public function original_subject_html_is_escaped(): void
    {
        $html = (string) $this->view('emails.forward.html', $this->bannerViewData([
            'replacedSubject' => '<script>alert(1)</script>',
        ]));

        $this->assertStringContainsString('Original&nbsp;subject:', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
    }

    #[Test]
    public function text_banner_keeps_banner_info_markers_and_has_no_actions_summary_line(): void
    {
        $text = (string) $this->view('emails.forward.text', [
            'locationText' => 'top',
            'text' => 'Hello',
            'aliasEmail' => 'alias@example.com',
            'aliasDescription' => 'Shop account',
            'fromEmail' => 'sender@example.com',
            'replacedSubject' => null,
            'deactivateUrl' => 'https://app.addy.io/deactivate/test',
            'blockEmailUrl' => 'https://app.addy.io/aliases/test/actions?action=block_email&signature=abc',
            'blockDomainUrl' => 'https://app.addy.io/aliases/test/actions?action=block_domain&signature=def',
        ]);

        $this->assertStringContainsString('<!--banner-info-->', $text);
        $this->assertStringContainsString("To: alias@example.com (Shop account)\nFrom: sender@example.com", $text);
        $this->assertStringNotContainsString('Description:', $text);
        $this->assertStringNotContainsString('Actions:', $text);
        $this->assertStringContainsString("Deactivate:\nhttps://app.addy.io/deactivate/test\n\nBlock email:", $text);
        $this->assertStringContainsString("action=block_email&signature=abc\n\nBlock domain:", $text);
        $this->assertStringContainsString("action=block_domain&signature=def\n--------------------", $text);
        $this->assertStringNotContainsString('&amp;', $text);
        $this->assertStringNotContainsString('Alias actions', $text);
        $this->assertStringNotContainsString('Click here', $text);
        $this->assertStringNotContainsString('to deactivate this alias', $text);
    }

    #[Test]
    public function text_banner_puts_original_subject_on_its_own_line(): void
    {
        $text = (string) $this->view('emails.forward.text', [
            'locationText' => 'top',
            'text' => 'Hello',
            'aliasEmail' => 'alias@example.com',
            'aliasDescription' => null,
            'fromEmail' => 'sender@example.com',
            'replacedSubject' => 'third tester',
            'deactivateUrl' => 'https://app.addy.io/deactivate/test',
            'blockEmailUrl' => null,
            'blockDomainUrl' => null,
        ]);

        $this->assertStringContainsString("From: sender@example.com\nOriginal subject: third tester\nDeactivate:", $text);
        $this->assertStringNotContainsString('Actions:', $text);
        $this->assertStringNotContainsString('with subject', $text);
    }

    #[Test]
    public function text_banner_omits_block_domain_when_no_domain_url_is_passed(): void
    {
        $text = (string) $this->view('emails.forward.text', [
            'locationText' => 'top',
            'text' => 'Hello',
            'aliasEmail' => 'alias@example.com',
            'aliasDescription' => null,
            'fromEmail' => 'will@anonaddy.com',
            'replacedSubject' => null,
            'deactivateUrl' => 'https://app.addy.io/deactivate/test',
            'blockEmailUrl' => 'https://app.addy.io/aliases/test/actions?action=block_email&email=will%40anonaddy.com&signature=abc',
            'blockDomainUrl' => null,
        ]);

        $this->assertStringContainsString("Deactivate:\nhttps://app.addy.io/deactivate/test\n\nBlock email:", $text);
        $this->assertStringNotContainsString('Actions:', $text);
        $this->assertStringNotContainsString('Block domain', $text);
        $this->assertStringContainsString('action=block_email&email=will%40anonaddy.com&signature=abc', $text);
        $this->assertStringNotContainsString('&amp;', $text);
    }
}
