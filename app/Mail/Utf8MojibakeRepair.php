<?php

namespace App\Mail;

/**
 * Undo common UTF-8 mojibake produced by Outlook (especially mobile) when multipart bodies disagree:
 * text/plain can be correct while text/html double-encodes the same glyphs (0xC3 0x83 0xC2 … sequences).
 * Clients such as Thunderbird prefer HTML in multipart/alternative, so signatures like "Grüßen" can render as
 * "GrÃ¼ÃŸen" in the visibly new region even though charset=utf-8 is correct at the MIME level.
 *
 * Repair maps UTF-8 through Windows-1252 octets back to UTF-8 (at most a few passes per run) — the usual
 * unwind for this Outlook bug class.
 *
 * When well-formed UTF-8 and mojibake appear in the same string (new lines correct, quoted history corrupted),
 * repairing the entire string at once fails {@see mb_check_encoding()}: correct characters become lone Latin-1
 * bytes between passes, so the whole fix is skipped. We unwind only contiguous segments that match unmistakable
 * octet patterns (longest first), then repeat until stable.
 */
final class Utf8MojibakeRepair
{
    private const MAX_PASSES = 4;

    /**
     * Corrupt runs from Outlook double-/triple-encoding: match triple (HTML quote blocks) before the C3 83 C2…
     * branches so alternation does not split a longer sequence.
     */
    private const OUTLOOK_MOJIBAKE_RUN_PATTERN = '/(?:\xC3\x83\xC6\x92\xC3\x82\xC2[\x80-\xBF]|\xC3\x83\xC2[\x80-\xBF]|\xC3\x83\xC5[\x80-\xBF])/';

    public static function unwindOutlookStyleMojibake(string $text): string
    {
        if ($text === '' || ! self::containsOutlookStyleMojibakeFingerprint($text)) {
            return $text;
        }

        $previous = null;

        while ($text !== $previous && self::containsOutlookStyleMojibakeFingerprint($text)) {
            $previous = $text;
            $text = preg_replace_callback(
                self::OUTLOOK_MOJIBAKE_RUN_PATTERN,
                static function (array $matches): string {
                    return self::unwindIsolatedOutlookStyleMojibake($matches[0]);
                },
                $text
            ) ?? $text;
        }

        return $text;
    }

    private static function unwindIsolatedOutlookStyleMojibake(string $fragment): string
    {
        $text = $fragment;

        for ($i = 0; $i < self::MAX_PASSES; $i++) {
            if (! self::containsOutlookStyleMojibakeFingerprint($text)) {
                break;
            }

            $repaired = mb_convert_encoding($text, 'Windows-1252', 'UTF-8');

            if ($repaired === $text) {
                break;
            }

            if (! mb_check_encoding($repaired, 'UTF-8')) {
                break;
            }

            $text = $repaired;
        }

        return $text;
    }

    private static function containsOutlookStyleMojibakeFingerprint(string $text): bool
    {
        return str_contains($text, "\xC3\x83\xC2")
            || str_contains($text, "\xC3\x83\xC6\x92")
            || str_contains($text, "\xC3\x83\xC5");
    }
}
