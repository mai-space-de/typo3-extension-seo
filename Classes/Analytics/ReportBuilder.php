<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Analytics;

final class ReportBuilder
{
    private const COLORS = ['#1d4ed8', '#0f766e', '#b45309', '#7c3aed', '#be123c', '#0369a1', '#4d7c0f', '#a16207'];

    public function __construct(
        private readonly AnalyticsStore $store,
        private readonly MetricsCalculator $calculator,
        private readonly SeriesFiller $seriesFiller,
        private readonly DurationFormatter $durations,
        private readonly TrafficChart $trafficChart,
        private readonly DonutChart $donutChart,
        private readonly Sparkline $sparkline,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(string $site, int $days, int $pageId = 0, ?int $now = null): array
    {
        $days = Period::normalize($days);
        $now ??= time();
        [$from, $to] = Period::window($days, $now);
        [$previousFrom, $previousTo] = Period::window($days, $from);
        $pageScope = $pageId > 0;

        $current = $this->calculator->summarize($this->store->sessions($from, $to, $site, $pageId), $pageScope);
        $previous = $this->calculator->summarize($this->store->sessions($previousFrom, $previousTo, $site, $pageId), $pageScope);
        $series = $this->seriesFiller->fill($this->store->daily($from, $to, $site, $pageId), $days, $now);
        $metrics = [
            $this->metric('visits', (string) $current->visits, $current->visits, $previous->visits, false),
            $this->metric('visitors', (string) $current->visitors, $current->visitors, $previous->visitors, false),
            $this->metric('bounce', $this->percent($current->bounceRate), $current->bounceRate, $previous->bounceRate, true),
            $this->metric('duration', $this->durations->format($current->avgDuration), $current->avgDuration, $previous->avgDuration, false),
            $this->metric('continuation', $this->percent($current->continuationRate), $current->continuationRate, $previous->continuationRate, false),
        ];
        $metrics[0]['sparkline'] = $this->sparkline->render($this->column($series, 'visits'));
        $metrics[1]['sparkline'] = $this->sparkline->render($this->column($series, 'visitors'));
        $channels = $pageScope ? [] : $this->paint($this->shares($this->store->breakdown('channel', $from, $to, $site)));
        $devices = $pageScope ? [] : $this->paint($this->shares($this->store->breakdown('device', $from, $to, $site)));
        $browsers = $pageScope ? [] : $this->paint($this->shares($this->store->breakdown('browser', $from, $to, $site)));
        $languages = $pageScope ? [] : $this->paint($this->shares($this->store->breakdown('locale', $from, $to, $site)));

        return [
            'days' => $days,
            'site' => $site,
            'pageId' => $pageId,
            'metrics' => $metrics,
            'series' => $series,
            'charts' => [
                'traffic' => $this->trafficChart->render($series),
                'devices' => $this->donutChart->render($devices),
                'browsers' => $this->donutChart->render($browsers),
            ],
            'topPages' => $pageScope ? [] : $this->withShare($this->store->topPages($from, $to, $site, 10)),
            'channels' => $channels,
            'devices' => $devices,
            'browsers' => $browsers,
            'languages' => $languages,
            'channelTotal' => $this->total($channels),
            'deviceTotal' => $this->total($devices),
            'browserTotal' => $this->total($browsers),
            'languageTotal' => $this->total($languages),
            'hasVisits' => $current->visits > 0,
        ];
    }

    /**
     * @return array{key: string, display: string, trendDisplay: string, tone: string, trendClass: string, direction: string, palette: string, icon: string, sparkline: string}
     */
    private function metric(string $key, string $display, float|int $current, float|int $previous, bool $lowerIsBetter): array
    {
        $trend = $this->trend($current, $previous);
        $tone = $this->tone($trend, $lowerIsBetter);

        return [
            'key' => $key,
            'display' => $display,
            'trendDisplay' => $trend === null ? '' : (($trend > 0 ? '+' : '') . number_format($trend, 1, ',', '') . ' %'),
            'tone' => $tone,
            'trendClass' => $tone === 'flat' ? 'neutral' : $tone,
            'direction' => $trend === null || $trend === 0.0 ? 'neutral' : ($trend > 0 ? 'up' : 'down'),
            'palette' => match ($key) {
                'visitors' => 'visitors',
                'bounce' => 'bounce-rate',
                'duration' => 'avg-duration',
                'continuation' => 'continuation-rate',
                default => 'visits',
            },
            'icon' => match ($key) {
                'visitors' => 'visitors',
                'bounce' => 'bounce',
                'duration' => 'duration',
                'continuation' => 'continuation',
                default => 'visits',
            },
            'sparkline' => '',
        ];
    }

    /**
     * @param list<array{date: string, visits: int, visitors: int, height: int}> $series
     * @return list<int>
     */
    private function column(array $series, string $key): array
    {
        $values = [];
        foreach ($series as $point) {
            $values[] = $key === 'visitors' ? $point['visitors'] : $point['visits'];
        }

        return $values;
    }

    private function trend(float|int $current, float|int $previous): ?float
    {
        if ((float) $previous === 0.0) {
            return (float) $current === 0.0 ? 0.0 : null;
        }

        return round(((float) $current - (float) $previous) / (float) $previous * 100, 1);
    }

    private function tone(?float $trend, bool $lowerIsBetter): string
    {
        if ($trend === null || $trend === 0.0) {
            return 'flat';
        }

        $improved = $lowerIsBetter ? $trend < 0 : $trend > 0;

        return $improved ? 'up' : 'down';
    }

    private function percent(float $value): string
    {
        return number_format($value, 1, ',', '') . ' %';
    }

    /**
     * @param list<array{label: string, count: int}> $rows
     * @return list<array{label: string, count: int, percent: float, tone: string}>
     */
    private function shares(array $rows): array
    {
        $total = 0;
        foreach ($rows as $row) {
            $total += $row['count'];
        }

        $shares = [];
        foreach ($rows as $index => $row) {
            $label = $row['label'] !== '' ? $row['label'] : 'unbekannt';
            $shares[] = [
                'label' => $this->label($label),
                'count' => $row['count'],
                'percent' => $total > 0 ? round($row['count'] / $total * 100, 1) : 0.0,
                'tone' => $this->shareTone($label, $index),
            ];
        }

        return $shares;
    }

    /**
     * @param list<array{count: int}> $rows
     */
    private function total(array $rows): int
    {
        $total = 0;
        foreach ($rows as $row) {
            $total += $row['count'];
        }

        return $total;
    }

    private function shareTone(string $value, int $index): string
    {
        return match ($value) {
            'direct' => 'source-direct',
            'search' => 'source-search',
            'social' => 'source-social',
            'campaign' => 'source-paid',
            'referral' => 'source-other',
            'desktop' => 'device-desktop',
            'mobile' => 'device-phone',
            'tablet' => 'device-tablet',
            'unbekannt', 'Sonstige' => 'series-other',
            default => 'series-' . (($index % 8) + 1),
        };
    }

    /**
     * @param list<array{label: string, count: int, percent: float, tone: string}> $rows
     * @return list<array{label: string, count: int, percent: float, tone: string, color: string}>
     */
    private function paint(array $rows): array
    {
        foreach ($rows as $index => $row) {
            if ($row['tone'] === '') {
                $rows[$index]['tone'] = 'series-' . (($index % 8) + 1);
            }
            $rows[$index]['color'] = self::COLORS[$index % count(self::COLORS)];
        }

        return $rows;
    }

    /**
     * @param list<array{pageId: int, path: string, title: string, visits: int}> $pages
     * @return list<array{pageId: int, path: string, title: string, visits: int, share: int}>
     */
    private function withShare(array $pages): array
    {
        $max = 1;
        foreach ($pages as $page) {
            $max = max($max, $page['visits']);
        }
        foreach ($pages as $index => $page) {
            $pages[$index]['share'] = (int) round($page['visits'] / $max * 100);
        }

        return $pages;
    }

    private function label(string $value): string
    {
        return match ($value) {
            'direct' => 'Direkt',
            'search' => 'Suche',
            'social' => 'Social',
            'campaign' => 'Kampagne',
            'referral' => 'Verweis',
            'desktop' => 'Desktop',
            'mobile' => 'Mobil',
            'tablet' => 'Tablet',
            'unbekannt' => 'Unbekannt',
            default => $value,
        };
    }
}
