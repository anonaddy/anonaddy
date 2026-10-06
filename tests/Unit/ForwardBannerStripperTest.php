<?php

namespace Tests\Unit;

use App\Mail\ForwardBannerStripper;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ForwardBannerStripperTest extends TestCase
{
    #[Test]
    public function it_removes_old_plain_text_banner_between_markers(): void
    {
        $text = <<<'TEXT'
Hello

<!--banner-info-->
This email was sent to alias@example.com from sender@example.com.
To deactivate this alias copy and paste the url below into your web browser.

https://app.addy.io/deactivate/old
<!--banner-info-->

Reply body
TEXT;

        $result = ForwardBannerStripper::stripText($text);

        $this->assertStringContainsString('Hello', $result);
        $this->assertStringContainsString('Reply body', $result);
        $this->assertStringNotContainsString('banner-info', $result);
        $this->assertStringNotContainsString('app.addy.io/deactivate/old', $result);
    }

    #[Test]
    public function it_removes_new_plain_text_banner_between_markers(): void
    {
        $text = <<<'TEXT'
Hello

<!--banner-info-->
This email was sent to alias@example.com from sender@example.com.
Deactivate this alias:

https://app.addy.io/deactivate/new
Alias actions:

https://app.addy.io/aliases/test/actions
<!--banner-info-->

Reply body
TEXT;

        $result = ForwardBannerStripper::stripText($text);

        $this->assertStringContainsString('Hello', $result);
        $this->assertStringContainsString('Reply body', $result);
        $this->assertStringNotContainsString('Deactivate this alias', $result);
        $this->assertStringNotContainsString('Alias actions', $result);
        $this->assertStringNotContainsString('app.addy.io/deactivate/new', $result);
    }

    #[Test]
    public function it_removes_escaped_banner_info_comments(): void
    {
        $text = '&lt;!--banner-info--&gt;This email was sent to alias@example.com from a@b.com. Deactivate this alias: https://app.addy.io/deactivate/x&lt;!--banner-info--&gt;Thanks';

        $result = ForwardBannerStripper::stripText($text);

        $this->assertSame('Thanks', $result);
    }

    #[Test]
    public function it_removes_old_html_quoted_as_text_using_the_legacy_phrase(): void
    {
        $text = "On 1 Jan Alice wrote:\nThis email was sent to alias@example.com from a@b.com.\nClick here to deactivate this alias\n\nMy reply";

        $result = ForwardBannerStripper::stripText($text);

        $this->assertStringContainsString('On 1 Jan Alice wrote:', $result);
        $this->assertStringContainsString('My reply', $result);
        $this->assertStringNotContainsString('This email was sent to', $result);
        $this->assertStringNotContainsString('to deactivate this alias', $result);
    }

    #[Test]
    public function it_removes_new_html_quoted_as_text_using_deactivate_this_alias(): void
    {
        $text = "On 1 Jan Alice wrote:\nThis email was sent to alias@example.com from a@b.com.\nDeactivate this alias. Alias actions\n\nMy reply";

        $result = ForwardBannerStripper::stripText($text);

        $this->assertStringContainsString('On 1 Jan Alice wrote:', $result);
        $this->assertStringContainsString('My reply', $result);
        $this->assertStringNotContainsString('This email was sent to', $result);
        $this->assertStringNotContainsString('Deactivate this alias', $result);
    }

    #[Test]
    public function it_removes_html_quoted_as_text_using_the_actions_line(): void
    {
        $text = "On 1 Jan Alice wrote:\nThis email was sent to alias@example.com from a@b.com.\nActions: Deactivate | Block email | Block domain\n\nMy reply";

        $result = ForwardBannerStripper::stripText($text);

        $this->assertStringContainsString('On 1 Jan Alice wrote:', $result);
        $this->assertStringContainsString('My reply', $result);
        $this->assertStringNotContainsString('This email was sent to', $result);
        $this->assertStringNotContainsString('Actions: Deactivate', $result);
        $this->assertStringNotContainsString('Block email', $result);
        $this->assertStringNotContainsString('Block domain', $result);
    }

    #[Test]
    public function it_removes_html_quoted_as_text_using_to_from_and_actions_lines(): void
    {
        $text = "On 1 Jan Alice wrote:\nTo: alias@example.com (Shop account)\nFrom: a@b.com\nOriginal subject: Hello\nActions: Deactivate | Block email | Block domain\nBlocked 4 tracking pixels. View report\n\nMy reply";

        $result = ForwardBannerStripper::stripText($text);

        $this->assertStringContainsString('On 1 Jan Alice wrote:', $result);
        $this->assertStringContainsString('My reply', $result);
        $this->assertStringNotContainsString('To: alias@example.com', $result);
        $this->assertStringNotContainsString('Original subject:', $result);
        $this->assertStringNotContainsString('Actions: Deactivate', $result);
        $this->assertStringNotContainsString('Blocked 4 tracking pixels', $result);
    }

    #[Test]
    public function it_removes_html_quoted_as_text_using_removed_tracking_pixels(): void
    {
        $text = "On 1 Jan Alice wrote:\nTo: alias@example.com (Shop account)\nFrom: a@b.com\nActions: Deactivate | Block email | Block domain\nRemoved 4 tracking pixels. View report\n\nMy reply";

        $result = ForwardBannerStripper::stripText($text);

        $this->assertStringContainsString('On 1 Jan Alice wrote:', $result);
        $this->assertStringContainsString('My reply', $result);
        $this->assertStringNotContainsString('To: alias@example.com', $result);
        $this->assertStringNotContainsString('Removed 4 tracking pixels', $result);
    }

    #[Test]
    public function it_removes_html_quoted_as_text_using_removed_tracking_pixels_with_a_hyphen(): void
    {
        $text = "On 1 Jan Alice wrote:\nTo: alias@example.com (Shop account)\nFrom: a@b.com\nActions: Deactivate | Block email | Block domain\nRemoved 4 tracking pixels - View report\n\nMy reply";

        $result = ForwardBannerStripper::stripText($text);

        $this->assertStringContainsString('On 1 Jan Alice wrote:', $result);
        $this->assertStringContainsString('My reply', $result);
        $this->assertStringNotContainsString('Removed 4 tracking pixels', $result);
    }

    #[Test]
    public function it_removes_a_flattened_to_from_actions_banner(): void
    {
        $text = "On 1 Jan Alice wrote:\nTo: alias@example.com From: a@b.com Actions: Deactivate | Block email\n\nMy reply";

        $result = ForwardBannerStripper::stripText($text);

        $this->assertStringContainsString('On 1 Jan Alice wrote:', $result);
        $this->assertStringContainsString('My reply', $result);
        $this->assertStringNotContainsString('To: alias@example.com', $result);
        $this->assertStringNotContainsString('Actions: Deactivate', $result);
    }

    #[Test]
    public function it_does_not_strip_normal_quoted_to_and_from_headers(): void
    {
        $text = "On 1 Jan Alice wrote:\nTo: alice@example.com\nFrom: bob@example.com\nSubject: Hello\n\nHello Will\n\nMy reply";

        $result = ForwardBannerStripper::stripText($text);

        $this->assertStringContainsString('To: alice@example.com', $result);
        $this->assertStringContainsString('From: bob@example.com', $result);
        $this->assertStringContainsString('Hello Will', $result);
        $this->assertStringContainsString('My reply', $result);
    }

    #[Test]
    public function it_removes_the_html_banner_row_that_contains_the_deactivate_url(): void
    {
        config(['app.url' => 'https://app.addy.io']);

        $html = <<<'HTML'
<table>
<tbody>
<tr>
    <td>
        <div id="addy-banner">This email was sent to alias@example.com from a@b.com.
        Actions: <a href="https://app.addy.io/deactivate/abc">Deactivate</a> | <a href="https://app.addy.io/aliases/abc/actions?action=block_email">Block email</a> | <a href="https://app.addy.io/aliases/abc/actions?action=block_domain">Block domain</a></div>
    </td>
</tr>
<tr>
    <td>Original message</td>
</tr>
</tbody>
</table>
HTML;

        $result = ForwardBannerStripper::stripHtml($html);

        $this->assertStringContainsString('Original message', $result);
        $this->assertStringNotContainsString('addy-banner', $result);
        $this->assertStringNotContainsString('Deactivate', $result);
        $this->assertStringNotContainsString('Block email', $result);
        $this->assertStringNotContainsString('app.addy.io/deactivate/abc', $result);
    }

    #[Test]
    public function it_removes_html_banner_rows_for_the_current_app_url_host(): void
    {
        config(['app.url' => 'https://app.addyto.me']);

        $html = <<<'HTML'
<table>
<tbody>
<tr>
    <td>
        <div id="addy-banner">To: alias@example.com
        Actions: <a href="https://app.addyto.me/deactivate/abc">Deactivate</a> | <a href="https://app.addyto.me/aliases/abc/actions?action=block_email">Block email</a></div>
    </td>
</tr>
<tr>
    <td>Original message</td>
</tr>
</tbody>
</table>
HTML;

        $result = ForwardBannerStripper::stripHtml($html);

        $this->assertStringContainsString('Original message', $result);
        $this->assertStringNotContainsString('addy-banner', $result);
        $this->assertStringNotContainsString('Deactivate', $result);
        $this->assertStringNotContainsString('app.addyto.me/deactivate/abc', $result);
    }

    #[Test]
    public function it_does_not_strip_staging_html_banners_when_app_url_is_production(): void
    {
        config(['app.url' => 'https://app.addy.io']);

        $html = <<<'HTML'
<table>
<tbody>
<tr>
    <td>
        <div id="addy-banner">To: alias@example.com
        Actions: <a href="https://app.addyto.me/deactivate/abc">Deactivate</a></div>
    </td>
</tr>
<tr>
    <td>Original message</td>
</tr>
</tbody>
</table>
HTML;

        $result = ForwardBannerStripper::stripHtml($html);

        $this->assertStringContainsString('Original message', $result);
        $this->assertStringContainsString('app.addyto.me/deactivate/abc', $result);
        $this->assertFalse(ForwardBannerStripper::containsDeactivateUrl($result));
    }

    #[Test]
    public function it_removes_legacy_html_banner_rows_for_anonaddy_hosts(): void
    {
        $html = '<table><tr><td>This email was sent to a@b.com from c@d.com. Click <a href="https://app.anonaddy.com/deactivate/abc">here</a> to deactivate this alias</td></tr><tr><td>Body</td></tr></table>';

        $result = ForwardBannerStripper::stripHtml($html);

        $this->assertStringContainsString('Body', $result);
        $this->assertStringNotContainsString('deactivate this alias', $result);
        $this->assertStringNotContainsString('anonaddy.com/deactivate', $result);
    }

    #[Test]
    public function it_removes_labelled_signed_urls_when_html_is_quoted_as_text(): void
    {
        config(['app.url' => 'https://app.addy.io']);

        $text = <<<'TEXT'
On 1 Jan Alice wrote:
To: alias@example.com (Shop account)
From: a@b.com
Actions: Deactivate | Block email | Block domain
Deactivate:
https://app.addy.io/deactivate/abc?signature=xyz

Block email:
https://app.addy.io/aliases/abc/actions?email=a%40b.com&action=block_email&signature=aaa

Block domain:
https://app.addy.io/aliases/abc/actions?email=a%40b.com&action=block_domain&signature=bbb

Removed 4 tracking pixels - View report:
https://app.addy.io/email/report?payload=test&signature=ccc

My reply
TEXT;

        $result = ForwardBannerStripper::stripText($text);

        $this->assertStringContainsString('On 1 Jan Alice wrote:', $result);
        $this->assertStringContainsString('My reply', $result);
        $this->assertStringNotContainsString('To: alias@example.com', $result);
        $this->assertStringNotContainsString('Actions: Deactivate', $result);
        $this->assertStringNotContainsString('Deactivate:', $result);
        $this->assertStringNotContainsString('Block email:', $result);
        $this->assertStringNotContainsString('Block domain:', $result);
        $this->assertStringNotContainsString('View report:', $result);
        $this->assertStringNotContainsString('app.addy.io/deactivate/abc', $result);
        $this->assertStringNotContainsString('action=block_email', $result);
        $this->assertStringNotContainsString('action=block_domain', $result);
        $this->assertStringNotContainsString('email/report', $result);
    }

    #[Test]
    public function it_removes_bare_signed_urls_when_html_is_quoted_as_text(): void
    {
        config(['app.url' => 'https://app.addy.io']);

        $text = <<<'TEXT'
On 1 Jan Alice wrote:
To: alias@example.com
From: a@b.com
Actions: Deactivate | Block email
<https://app.addy.io/deactivate/abc?signature=xyz>
<https://app.addy.io/aliases/abc/actions?email=a%40b.com&action=block_email&signature=aaa>
Removed 2 tracking pixels - View report
<https://app.addy.io/email/report?payload=test&signature=ccc>

My reply
TEXT;

        $result = ForwardBannerStripper::stripText($text);

        $this->assertStringContainsString('On 1 Jan Alice wrote:', $result);
        $this->assertStringContainsString('My reply', $result);
        $this->assertStringNotContainsString('Actions: Deactivate', $result);
        $this->assertStringNotContainsString('app.addy.io/deactivate/abc', $result);
        $this->assertStringNotContainsString('action=block_email', $result);
        $this->assertStringNotContainsString('email/report', $result);
    }

    #[Test]
    public function it_removes_a_text_banner_without_an_actions_summary_line(): void
    {
        config(['app.url' => 'https://app.addy.io']);

        $text = <<<'TEXT'
On 1 Jan Alice wrote:
To: alias@example.com (Shop account)
From: a@b.com
Original subject: Hello
Deactivate:
https://app.addy.io/deactivate/abc?signature=xyz

Block email:
https://app.addy.io/aliases/abc/actions?email=a%40b.com&action=block_email&signature=aaa

My reply
TEXT;

        $result = ForwardBannerStripper::stripText($text);

        $this->assertStringContainsString('On 1 Jan Alice wrote:', $result);
        $this->assertStringContainsString('My reply', $result);
        $this->assertStringNotContainsString('To: alias@example.com', $result);
        $this->assertStringNotContainsString('Original subject:', $result);
        $this->assertStringNotContainsString('Deactivate:', $result);
        $this->assertStringNotContainsString('app.addy.io/deactivate/abc', $result);
        $this->assertStringNotContainsString('action=block_email', $result);
    }

    #[Test]
    public function it_does_not_strip_unrelated_urls_or_normal_quoted_headers(): void
    {
        config(['app.url' => 'https://app.addy.io']);

        $text = "On 1 Jan Alice wrote:\nTo: alice@example.com\nFrom: bob@example.com\nSubject: Hello\n\nSee https://example.com/deactivate/foo\n\nMy reply";

        $result = ForwardBannerStripper::stripText($text);

        $this->assertStringContainsString('To: alice@example.com', $result);
        $this->assertStringContainsString('From: bob@example.com', $result);
        $this->assertStringContainsString('https://example.com/deactivate/foo', $result);
        $this->assertStringContainsString('My reply', $result);
    }

    #[Test]
    public function it_removes_a_quoted_html_banner_div(): void
    {
        config(['app.url' => 'https://app.addy.io']);

        $html = <<<'HTML'
<blockquote>
<div id="addy-banner" class="addy-banner">To: alias@example.com
Actions: <a href="https://app.addy.io/deactivate/abc">Deactivate</a> | <a href="https://app.addy.io/aliases/abc/actions?action=block_email">Block email</a></div>
</blockquote>
<p>Original message</p>
HTML;

        $result = ForwardBannerStripper::stripHtml($html);

        $this->assertStringContainsString('Original message', $result);
        $this->assertStringNotContainsString('addy-banner', $result);
        $this->assertStringNotContainsString('Deactivate', $result);
        $this->assertStringNotContainsString('app.addy.io/deactivate/abc', $result);
    }
}
