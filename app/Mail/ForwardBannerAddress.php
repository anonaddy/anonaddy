<?php

namespace App\Mail;

use App\Models\Alias;
use Illuminate\Support\Str;
use Throwable;

final class ForwardBannerAddress
{
    /**
     * Address to show on forward banners (“This email was sent to …”).
     *
     * Prefers the real SMTP envelope recipient (includes +detail extensions), then tries the inbound
     * To header value, falling back to the stored alias mailbox.
     */
    public static function forBanner(?string $smtpInboundRecipient, ?string $originalToHeader, Alias $alias): string
    {
        if ($smtpInboundRecipient !== null && $smtpInboundRecipient !== '') {
            $trimmed = trim($smtpInboundRecipient);
            if (filter_var($trimmed, FILTER_VALIDATE_EMAIL)) {
                return Str::lower($trimmed);
            }
        }

        if ($originalToHeader !== null && $originalToHeader !== '') {
            try {
                $addresses = mailparse_rfc822_parse_addresses($originalToHeader);

                $firstAddress = $addresses[0]['address'] ?? null;
                if ($firstAddress && filter_var($firstAddress, FILTER_VALIDATE_EMAIL)) {
                    return Str::lower($firstAddress);
                }
            } catch (Throwable) {
                // ignore malformed inbound To headers
            }
        }

        return Str::lower($alias->email);
    }
}
