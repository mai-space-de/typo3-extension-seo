<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\Analytics;

use Maispace\MaiSeo\Analytics\DonutChart;
use Maispace\MaiSeo\Analytics\TrafficChart;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ChartTest extends TestCase
{
    #[Test]
    public function trafficChartDrawsBothSeriesAndSkipsOverlappingDates(): void
    {
        $series = [];
        for ($day = 1; $day <= 14; $day++) {
            $series[] = [
                'date' => sprintf('2026-11-%02d', $day),
                'visits' => $day,
                'visitors' => max(1, intdiv($day, 2)),
            ];
        }

        $svg = (new TrafficChart())->render($series);

        self::assertStringContainsString('data-tone="visits"', $svg);
        self::assertStringContainsString('data-tone="visitors"', $svg);
        self::assertStringContainsString('1.11.', $svg);
        self::assertStringContainsString('14.11.', $svg);
        self::assertStringNotContainsString('>2.11.<', $svg);
        self::assertStringContainsString('Aufrufe: 14', $svg);
    }

    #[Test]
    public function donutUsesSegmentColorsAndSurvivesAnEmptySet(): void
    {
        $svg = (new DonutChart())->render([
            ['label' => 'Desktop', 'count' => 3, 'percent' => 75.0, 'color' => '#1d4ed8', 'tone' => 'device-desktop'],
            ['label' => 'Mobil', 'count' => 1, 'percent' => 25.0, 'color' => '#0f766e', 'tone' => 'device-phone'],
        ]);

        self::assertStringContainsString('device-desktop', $svg);
        self::assertStringContainsString('device-phone', $svg);
        self::assertStringContainsString('mai-seo-donut-empty', (new DonutChart())->render([]));
    }
}
