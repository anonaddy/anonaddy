<?php

namespace App\Services;

class WebhookUrlGuard
{
    public const PUBLIC_HTTPS_MESSAGE = 'The webhook URL must be a public https address.';

    public const UNRESOLVED_HOST_MESSAGE = 'The webhook URL host could not be resolved.';

    /**
     * @var list<string>
     */
    protected array $blockedHosts = [
        'localhost',
        'metadata.google.internal',
    ];

    /**
     * Ranges that FILTER_FLAG_NO_PRIV_RANGE and FILTER_FLAG_NO_RES_RANGE allow but that can reach internal hosts.
     *
     * @var list<string>
     */
    protected array $blockedRanges = [
        '100.64.0.0/10', // Carrier-grade NAT, also cloud metadata e.g. 100.100.100.200
        '192.0.0.0/24', // IETF protocol assignments
        '198.18.0.0/15', // Benchmarking
        '64:ff9b::/96', // NAT64
        '64:ff9b:1::/48', // Local-use NAT64
        '2001::/32', // Teredo
        '2002::/16', // 6to4
        'fec0::/10', // Deprecated site-local
    ];

    public function isAllowed(string $url): bool
    {
        return $this->failureReason($url) === null;
    }

    public function failureReason(string $url): ?string
    {
        $inspected = $this->inspect($url);

        return is_string($inspected) ? $inspected : null;
    }

    /**
     * One accepted address from the same lookup that allowed the URL.
     *
     * @return array{host: string, port: int, address: string, literal: bool}|null
     */
    public function connectionPin(string $url): ?array
    {
        $inspected = $this->inspect($url);

        if (is_string($inspected)) {
            return null;
        }

        return [
            'host' => $inspected['host'],
            'port' => $inspected['port'],
            'address' => $this->preferredAddress($inspected['addresses']),
            'literal' => $inspected['literal'],
        ];
    }

    public function curlResolveLine(string $host, int $port, string $address): string
    {
        if (str_contains($address, ':')) {
            $address = '['.$address.']';
        }

        return $host.':'.$port.':'.$address;
    }

    /**
     * @return array{host: string, port: int, addresses: list<string>, literal: bool}|string
     */
    protected function inspect(string $url): array|string
    {
        if (strlen($url) > 2000 || preg_match('/\s/', $url) === 1) {
            return self::PUBLIC_HTTPS_MESSAGE;
        }

        $parts = parse_url($url);

        if (! is_array($parts)) {
            return self::PUBLIC_HTTPS_MESSAGE;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));

        if ($scheme !== 'https' || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])) {
            return self::PUBLIC_HTTPS_MESSAGE;
        }

        $host = strtolower(trim((string) ($parts['host'] ?? ''), '[]'));
        $port = isset($parts['port']) ? (int) $parts['port'] : 443;

        if ($host === '' || $port < 1 || $port > 65535 || $this->hostIsBlocked($host)) {
            return self::PUBLIC_HTTPS_MESSAGE;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return $this->addressIsDisallowed($host)
                ? self::PUBLIC_HTTPS_MESSAGE
                : [
                    'host' => $host,
                    'port' => $port,
                    'addresses' => [$host],
                    'literal' => true,
                ];
        }

        $addresses = $this->resolveHost($host);

        if ($addresses === []) {
            return self::UNRESOLVED_HOST_MESSAGE;
        }

        foreach ($addresses as $address) {
            if ($this->addressIsDisallowed($address)) {
                return self::PUBLIC_HTTPS_MESSAGE;
            }
        }

        return [
            'host' => $host,
            'port' => $port,
            'addresses' => $addresses,
            'literal' => false,
        ];
    }

    /**
     * @param  list<string>  $addresses
     */
    protected function preferredAddress(array $addresses): string
    {
        foreach ($addresses as $address) {
            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $address;
            }
        }

        return $addresses[0];
    }

    /**
     * Look up the absolute name. A trailing dot stops the resolver from appending a search domain.
     *
     * @return list<string>
     */
    public function resolveHost(string $host): array
    {
        $records = dns_get_record($this->absoluteDnsName($host), DNS_A + DNS_AAAA);

        if (! is_array($records)) {
            return [];
        }

        $addresses = [];

        foreach ($records as $record) {
            if (isset($record['ip']) && is_string($record['ip'])) {
                $addresses[] = $record['ip'];
            }

            if (isset($record['ipv6']) && is_string($record['ipv6'])) {
                $addresses[] = $record['ipv6'];
            }
        }

        return $addresses;
    }

    protected function absoluteDnsName(string $host): string
    {
        return rtrim($host, '.').'.';
    }

    public function addressIsDisallowed(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }

        foreach ($this->blockedRanges as $range) {
            if ($this->addressIsInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    protected function addressIsInRange(string $ip, string $range): bool
    {
        [$subnet, $bits] = explode('/', $range);

        $address = inet_pton($ip);
        $subnet = inet_pton($subnet);

        // Compare IPv4 with IPv4 ranges and IPv6 with IPv6 ranges only
        if ($address === false || $subnet === false || strlen($address) !== strlen($subnet)) {
            return false;
        }

        $bits = (int) $bits;
        $bytes = intdiv($bits, 8);

        if (substr($address, 0, $bytes) !== substr($subnet, 0, $bytes)) {
            return false;
        }

        if ($remainder = $bits % 8) {
            $mask = (0xFF << (8 - $remainder)) & 0xFF;

            return (ord($address[$bytes]) & $mask) === (ord($subnet[$bytes]) & $mask);
        }

        return true;
    }

    protected function hostIsBlocked(string $host): bool
    {
        if (in_array($host, $this->blockedHosts, true)) {
            return true;
        }

        if (preg_match('/^\d+$/', $host) === 1) {
            return true;
        }

        return str_ends_with($host, '.local')
            || str_ends_with($host, '.internal')
            || str_ends_with($host, '.localhost');
    }
}
