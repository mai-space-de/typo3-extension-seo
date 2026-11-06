<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Analytics;

final class MetricsCalculator
{
    /**
     * @param list<array{hits: int, duration: int, page_hits: int, page_duration: int}> $sessions
     */
    public function summarize(array $sessions, bool $pageScope): PerformanceSnapshot
    {
        $relevant = [];
        foreach ($sessions as $session) {
            if ($pageScope && $session['page_hits'] < 1) {
                continue;
            }
            $relevant[] = $session;
        }

        $visits = 0;
        $durationSum = 0;
        $durationSamples = 0;
        $bounces = 0;
        foreach ($relevant as $session) {
            $visits += $pageScope ? $session['page_hits'] : $session['hits'];
            if ($session['hits'] === 1) {
                ++$bounces;
            }
            $duration = $pageScope ? $session['page_duration'] : $session['duration'];
            if ($duration > 0) {
                $durationSum += $duration;
                ++$durationSamples;
            }
        }

        $visitors = count($relevant);
        $bounceRate = $visitors > 0 ? round($bounces / $visitors * 100, 1) : 0.0;

        return new PerformanceSnapshot(
            visits: $visits,
            visitors: $visitors,
            bounceRate: $bounceRate,
            avgDuration: $durationSamples > 0 ? (int) round($durationSum / $durationSamples) : 0,
            continuationRate: round(100 - $bounceRate, 1),
        );
    }
}
