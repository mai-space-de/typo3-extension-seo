<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\Analytics;

use Maispace\MaiSeo\Analytics\DurationFormatter;
use Maispace\MaiSeo\Analytics\MetricsCalculator;
use Maispace\MaiSeo\Analytics\Period;
use Maispace\MaiSeo\Analytics\SeriesFiller;
use Maispace\MaiSeo\Analytics\Sparkline;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ReportMathTest extends TestCase
{
    #[Test]
    public function summarizesBounceRateAndIgnoresOtherPages(): void
    {
        $snapshot = (new MetricsCalculator())->summarize([
            ['hits' => 1, 'duration' => 12, 'page_hits' => 1, 'page_duration' => 12],
            ['hits' => 3, 'duration' => 40, 'page_hits' => 1, 'page_duration' => 15],
            ['hits' => 2, 'duration' => 20, 'page_hits' => 0, 'page_duration' => 0],
        ], true);

        self::assertSame(2, $snapshot->visits);
        self::assertSame(2, $snapshot->visitors);
        self::assertSame(50.0, $snapshot->bounceRate);
        self::assertSame(50.0, $snapshot->continuationRate);
        self::assertSame(14, $snapshot->avgDuration);
    }

    #[Test]
    public function fillsMissingDaysAndFormatsDuration(): void
    {
        self::assertSame(14, Period::normalize(14));
        self::assertSame(7, Period::normalize(3));

        $series = (new SeriesFiller())->fill([
            ['day_index' => (int) floor(1_700_000_000 / 86400), 'visits' => 4, 'visitors' => 2],
        ], 7, 1_700_000_000);

        self::assertCount(7, $series);
        self::assertSame(4, array_sum(array_column($series, 'visits')));
        self::assertSame('1m 5s', (new DurationFormatter())->format(65));
        self::assertStringContainsString('mai-seo-spark-line', (new Sparkline())->render([1, 3, 2]));
    }
}
