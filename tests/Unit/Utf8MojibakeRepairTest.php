<?php

namespace Tests\Unit;

use App\Mail\Utf8MojibakeRepair;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Utf8MojibakeRepairTest extends TestCase
{
    #[Test]
    public function correct_utf8_german_is_unchanged(): void
    {
        $text = 'Mit freundlichen Grüßen';

        $this->assertSame($text, Utf8MojibakeRepair::unwindOutlookStyleMojibake($text));
    }

    #[Test]
    public function outlook_double_encoded_signature_is_repaired(): void
    {
        $corrupt = 'Mit freundlichen Gr'."\xC3\x83\xC2\xBC\xC3\x83\xC5\xB8".'en';

        $fixed = Utf8MojibakeRepair::unwindOutlookStyleMojibake($corrupt);

        $this->assertSame('Mit freundlichen Grüßen', $fixed);
    }

    #[Test]
    public function triple_layer_html_style_umlaut_requires_two_passes(): void
    {
        // Similar to QP fragment =C3=83=C6=92=C3=82=C2=B6 decoded from Outlook HTML branches
        $corrupt = "\xC3\x83\xC6\x92\xC3\x82\xC2\xB6";

        $fixed = Utf8MojibakeRepair::unwindOutlookStyleMojibake($corrupt);

        $this->assertSame('ö', $fixed);
    }

    #[Test]
    public function empty_string_is_unchanged(): void
    {
        $this->assertSame('', Utf8MojibakeRepair::unwindOutlookStyleMojibake(''));
    }

    #[Test]
    public function correct_utf8_and_outlook_mojibake_in_one_message_both_get_repaired_segments(): void
    {
        // Realistic multipart/alternative disagree: new paragraph is valid UTF-8, quoted block is double-encoded.
        $body = "ä\n".'bad '."\xC3\x83\xC2\xBC";
        $fixed = Utf8MojibakeRepair::unwindOutlookStyleMojibake($body);

        $this->assertSame("ä\nbad ü", $fixed);
    }

    #[Test]
    public function correct_utf8_must_not_be_turned_into_invalid_utf8_when_corrupt_runs_exist(): void
    {
        // Whole-string mb_convert_encoding would yield 0xE4 for the first ä (invalid alone in UTF-8) and abort;
        // segmented repair must leave leading ä intact and fix only the corrupt run.
        $body = "\xC3\xA4\n"."\xC3\x83\xC2\xA4\n"."\xC3\x83\xC6\x92\xC3\x82\xc2\xa4";

        $fixed = Utf8MojibakeRepair::unwindOutlookStyleMojibake($body);

        $this->assertSame("\xC3\xA4\n\xC3\xA4\n\xC3\xA4", $fixed);
    }
}
