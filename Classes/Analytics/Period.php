<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Analytics;

final class Period
{
    /** @var list<int> */
    public const ALLOWED = [7, 14, 30];

    public static function normalize(int $days): int
    {
        return in_array($days, self::ALLOWED, true) ? $days : 7;
    }

    /**
     * @return array{0: int, 1: int}
     */
    public static function window(int $days, int $until): array
    {
        return [$until - $days * 86400, $until];
    }
}
