<?php

namespace App\Mail;

use Illuminate\Support\Str;

final class ForwardBannerStripper
{
    /**
     * Hosts that appear in signed deactivate URLs from previous app domains.
     *
     * @var list<string>
     */
    private const LEGACY_DEACTIVATE_HOSTS = [
        'app.anonaddy.com',
    ];

    /**
     * Remove quoted forward banners from a plain-text reply or send body.
     */
    public static function stripText(string $text): string
    {
        $url = self::quotedBannerUrlPattern();
        $labelSpace = '(?: |\x{00A0}|&nbsp;)+';
        $actionLink = '(?:\s*\[[0-9]+\])?(?:\s+'.$url.')?';
        $actionsLine = 'Actions:'.$labelSpace.'Deactivate'.$actionLink
            .'(?:\s*\|\s*Block email'.$actionLink.')?'
            .'(?:\s*\|\s*Block domain'.$actionLink.')?';
        $tail = '(?:'
            .'\n(?:Deactivate|Block email|Block domain|View report):'
            .'|\n(?:Blocked|Removed) \d+ tracking pixels?(?:\.| -)[^\n]*'
            .'|\n[ \t]*'.$url
            .'|\n[ \t]*\[[0-9]+\][ \t]*'.$url
            .')*';

        return (string) Str::of($text)
            ->replaceMatches('/((<|&lt;)!--banner-info--(&gt;|>)).*?((<|&lt;)!--banner-info--(&gt;|>))/mis', '')
            ->replaceMatches('/(This email was sent to).*?(to deactivate this alias)/mis', '')
            ->replaceMatches('/(This email was sent to).*?(Deactivate this alias)/mis', '')
            ->replaceMatches('/(This email was sent to).*?(Actions: Deactivate(?:\s*\|\s*Block email)?(?:\s*\|\s*Block domain)?)/mis', '')
            ->replaceMatches('/^To: [^\n]+\nFrom: [^\n]+\n(?:[^\n]+\n)?Actions: Deactivate(?:\s*\|\s*Block email)?(?:\s*\|\s*Block domain)?(?:\n(?:Blocked|Removed) \d+ tracking pixels?(?:\.| -)[^\n]*)?/mi', '')
            ->replaceMatches('/To: [^\n]+ From: [^\n]+ Actions: Deactivate(?:\s*\|\s*Block email)?(?:\s*\|\s*Block domain)?(?: (?:Blocked|Removed) \d+ tracking pixels?(?:\.| -)[^\n]*)?/mi', '')
            ->replaceMatches('/^To:'.$labelSpace.'[^\n]+\nFrom:'.$labelSpace.'[^\n]+\n(?:[^\n]+\n)?'.$actionsLine.$tail.'/miu', '')
            ->replaceMatches('/To:'.$labelSpace.'[^\n]+ From:'.$labelSpace.'[^\n]+ '.$actionsLine.'(?: (?:Blocked|Removed) \d+ tracking pixels?(?:\.| -)[^\n]*)?/miu', '')
            ->replaceMatches('/^To:'.$labelSpace.'[^\n]+\nFrom:'.$labelSpace.'[^\n]+\n(?:Original subject: [^\n]+\n)?Deactivate:\n[ \t]*'.$url.$tail.'/miu', '')
            ->replaceMatches('/(?:^|\n)(?:Deactivate|Block email|Block domain):\n[ \t]*'.$url.'/miu', '')
            ->replaceMatches('/(?:^|\n)(?:Blocked|Removed) \d+ tracking pixels?(?:\.| -) View report:\n[ \t]*'.$url.'/miu', '')
            ->replaceMatches('/(?:^|\n)[ \t]*'.$url.'/miu', '');
    }

    /**
     * Remove quoted forward banners from an HTML reply or send body.
     */
    public static function stripHtml(string $html): string
    {
        $hosts = implode('|', array_map(
            static fn (string $host): string => preg_quote($host, '/'),
            self::deactivateHosts()
        ));

        return (string) Str::of($html)
            ->replaceMatches('/((<|&lt;)!--banner-info--(&gt;|>)).*?((<|&lt;)!--banner-info--(&gt;|>))/mis', '')
            ->replaceMatches('/<div[^>]*id=["\']addy-banner["\'][^>]*>(?:(?!<div).)*?(?:'.$hosts.')(?:\/|%2F)deactivate(?:\/|%2F).*?<\/div>/mis', '')
            ->replaceMatches('/<tr(?:(?!<tr).)*?(?:'.$hosts.')(?:\/|%2F)deactivate(?:\/|%2F).*?\/tr>/mis', '');
    }

    /**
     * Whether HTML still contains a signed deactivate URL for a known app host.
     */
    public static function containsDeactivateUrl(string $html): bool
    {
        $needles = [];

        foreach (self::deactivateHosts() as $host) {
            $needles[] = $host.'/deactivate';
            $needles[] = $host.'%2Fdeactivate';
        }

        return Str::contains($html, $needles, true);
    }

    /**
     * Signed banner URLs for known app hosts, including a wrapped continuation line.
     */
    private static function quotedBannerUrlPattern(): string
    {
        $hosts = implode('|', array_map(
            static fn (string $host): string => preg_quote($host, '/'),
            self::deactivateHosts()
        ));

        return '<?https:\/\/(?:'.$hosts.')[^\s\n>]*(?:\/deactivate(?:\/|%2F)|\/aliases\/[^\/\s]+\/actions|\/email\/report)[^\s\n>]*>?(?:\n(?=[\/?&#%A-Za-z0-9=_-])[^\s\n]+)*';
    }

    /**
     * @return list<string>
     */
    private static function deactivateHosts(): array
    {
        $hosts = self::LEGACY_DEACTIVATE_HOSTS;
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (is_string($appHost) && $appHost !== '') {
            $hosts[] = $appHost;
        }

        return array_values(array_unique($hosts));
    }
}
