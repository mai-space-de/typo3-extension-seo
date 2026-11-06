<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Analytics;

/**
 * Tagesgenauer Besucher-Hash. Die IP wird nur in die Berechnung gegeben und nicht gespeichert.
 */
final class VisitorHasher
{
    public function hash(string $ip, string $userAgent, string $day, string $salt): string
    {
        return hash_hmac('sha256', $ip . '|' . $userAgent, $salt . '|' . $day);
    }
}
