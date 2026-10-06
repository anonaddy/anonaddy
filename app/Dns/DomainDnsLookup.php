<?php

namespace App\Dns;

class DomainDnsLookup
{
    /**
     * @return array<int, array<string, mixed>>|false
     */
    public function getRecords(string $hostname, int $type): array|false
    {
        return dns_get_record($hostname, $type);
    }
}
