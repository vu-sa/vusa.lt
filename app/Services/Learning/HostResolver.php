<?php

namespace App\Services\Learning;

class HostResolver
{
    /** @return list<string> */
    public function resolve(string $host): array
    {
        $addresses = [];
        foreach (dns_get_record($host, DNS_A | DNS_AAAA) ?: [] as $record) {
            if (isset($record['ip'])) {
                $addresses[] = $record['ip'];
            } elseif (isset($record['ipv6'])) {
                $addresses[] = $record['ipv6'];
            }
        }

        return array_values(array_unique([...$addresses, ...(gethostbynamel($host) ?: [])]));
    }
}
