<?php

namespace App\Mail;

use Illuminate\Support\Str;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Exception\InvalidArgumentException;
use Symfony\Component\Mime\Exception\RfcComplianceException;
use Throwable;

final class MailboxHeader
{
    /**
     * Build a single mailbox string that Symfony Address::create() can parse.
     */
    public static function format(?string $display, string $address): string
    {
        $address = trim($address);
        $display = self::normaliseDisplay($display, $address);

        if ($display === null) {
            return $address;
        }

        return $display.' <'.$address.'>';
    }

    /**
     * Turn a To/Cc header value into mailbox strings that Symfony will accept.
     *
     * @return list<string>
     */
    public static function forSymfony(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            return [];
        }

        try {
            Address::create($value);

            // Older Symfony can accept duplicated mailbox lists as one address.
            if (! preg_match('/<[^>]+>.*<[^>]+>/s', $value)) {
                return [$value];
            }
        } catch (RfcComplianceException|InvalidArgumentException) {
            // Parse duplicated or otherwise malformed mailbox lists.
        }

        $addresses = [];

        try {
            foreach (mailparse_rfc822_parse_addresses($value) ?: [] as $parsed) {
                $address = trim((string) ($parsed['address'] ?? ''));
                if ($address === '' || ! filter_var($address, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                $formatted = self::format($parsed['display'] ?? null, $address);

                try {
                    Address::create($formatted);
                    $addresses[] = $formatted;
                } catch (RfcComplianceException|InvalidArgumentException) {
                    $addresses[] = $address;
                }
            }
        } catch (Throwable) {
            // Fall through to regex extraction.
        }

        if ($addresses !== []) {
            return array_values(array_unique($addresses));
        }

        preg_match_all('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $value, $matches);

        $found = [];
        foreach ($matches[0] as $candidate) {
            $candidate = trim($candidate, '.,;');
            if (filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                $found[] = $candidate;
            }
        }

        return array_values(array_unique($found));
    }

    private static function normaliseDisplay(?string $display, string $address): ?string
    {
        if (! is_string($display)) {
            return null;
        }

        $display = trim($display);
        $display = trim($display, '"\'');

        if (Str::startsWith($display, '<') && Str::endsWith($display, '>')) {
            $display = trim(Str::substr($display, 1, -1));
        }

        if ($display === '' || strcasecmp($display, $address) === 0) {
            return null;
        }

        if (Str::contains($display, '<') || Str::contains($display, '>')) {
            return null;
        }

        return $display;
    }
}
