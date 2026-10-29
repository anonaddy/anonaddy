<?php

namespace App\Services;

use App\Models\Alias;
use App\Models\EmailData;

class InboundEmailWebhookPayload
{
    public const BODY_BYTE_CAP = 1048576;

    /**
     * @return array<string, mixed>
     */
    public static function make(Alias $alias, EmailData $emailData, string $emailType, bool $includeBody, bool $includeHeaders = false): array
    {
        $messageId = self::decode($emailData->messageId);
        $type = match ($emailType) {
            'R' => 'reply',
            'S' => 'send',
            default => 'forward',
        };

        $payload = [
            'id' => hash('sha256', $alias->id.'|'.$messageId.'|'.$type),
            'event' => 'alias.email.received',
            'email_type' => $type,
            'alias' => [
                'id' => $alias->id,
                'email' => $alias->email,
            ],
            'from' => is_string($emailData->sender) ? $emailData->sender : null,
            'subject' => self::decode($emailData->subject),
            'message_id' => $messageId,
            'size' => (int) $emailData->size,
            'attachment_count' => count($emailData->attachments) + count($emailData->inlineAttachments),
            'received_at' => now()->toIso8601String(),
        ];

        if ($includeHeaders) {
            $lines = is_array($emailData->headers) ? $emailData->headers : [];
            $payload += self::capHeaders($lines);
        }

        if (! $includeBody) {
            return $payload;
        }

        $capped = self::capBodies(self::decode($emailData->text), self::decode($emailData->html));

        return $payload + $capped;
    }

    /**
     * @param  list<string>  $lines
     * @return array{headers: list<array{name: string, value: string}>, headers_truncated: bool}
     */
    public static function capHeaders(array $lines): array
    {
        $headers = [];
        $used = 0;

        foreach ($lines as $line) {
            if (! is_string($line)) {
                continue;
            }

            $parts = explode(': ', $line, 2);

            if (count($parts) !== 2) {
                continue;
            }

            $name = strtolower($parts[0]);
            $value = $parts[1];

            if (self::headerIsExcluded($name)) {
                continue;
            }

            $bytes = strlen($name) + strlen($value);

            if ($used + $bytes > self::BODY_BYTE_CAP) {
                $remaining = self::BODY_BYTE_CAP - $used - strlen($name);

                if ($remaining > 0) {
                    $headers[] = [
                        'name' => $name,
                        'value' => mb_strcut($value, 0, $remaining, 'UTF-8'),
                    ];
                }

                return [
                    'headers' => $headers,
                    'headers_truncated' => true,
                ];
            }

            $headers[] = [
                'name' => $name,
                'value' => $value,
            ];
            $used += $bytes;
        }

        return [
            'headers' => $headers,
            'headers_truncated' => false,
        ];
    }

    protected static function headerIsExcluded(string $name): bool
    {
        return $name === 'received'
            || $name === 'dkim-signature'
            || $name === 'x-spamd-result'
            || str_starts_with($name, 'x-anonaddy-');
    }

    /**
     * @return array{text: string, html: string, truncated: bool}
     */
    public static function capBodies(string $text, string $html): array
    {
        if (strlen($text) + strlen($html) <= self::BODY_BYTE_CAP) {
            return [
                'text' => $text,
                'html' => $html,
                'truncated' => false,
            ];
        }

        $text = mb_strcut($text, 0, self::BODY_BYTE_CAP, 'UTF-8');
        $remaining = self::BODY_BYTE_CAP - strlen($text);

        return [
            'text' => $text,
            'html' => $remaining > 0 ? mb_strcut($html, 0, $remaining, 'UTF-8') : '',
            'truncated' => true,
        ];
    }

    protected static function decode(mixed $value): string
    {
        if (! is_string($value) || $value === '') {
            return '';
        }

        $decoded = base64_decode($value, true);

        return $decoded === false ? '' : $decoded;
    }
}
