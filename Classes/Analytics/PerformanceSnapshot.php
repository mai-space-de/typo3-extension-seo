<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Analytics;

final readonly class PerformanceSnapshot
{
    public function __construct(
        public int $visits,
        public int $visitors,
        public float $bounceRate,
        public int $avgDuration,
        public float $continuationRate,
    ) {}
}
