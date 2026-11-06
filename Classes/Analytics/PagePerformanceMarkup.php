<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Analytics;

final class PagePerformanceMarkup
{
    public function __construct(
        private readonly Sparkline $sparkline,
    ) {}

    /**
     * @param array<string, mixed> $report
     * @param list<array{days: int, url: string, active: bool}> $periods
     */
    public function render(array $report, array $periods, string $moduleUrl): string
    {
        $metrics = '';
        foreach ($report['metrics'] as $metric) {
            if (!is_array($metric) || !in_array($metric['key'], ['visits', 'bounce', 'duration', 'continuation'], true)) {
                continue;
            }
            $values = [];
            if ($metric['key'] === 'visits') {
                foreach ($report['series'] as $point) {
                    $values[] = (int) $point['visits'];
                }
            }
            $metrics .= '<div class="mai-seo-analytics-metric"><span>' . $this->caption((string) $metric['key']) . '</span><strong>'
                . htmlspecialchars((string) $metric['display'], ENT_QUOTES) . '</strong>'
                . $this->sparkline->render($values, 96, 28)
                . '<small class="mai-seo-tone-' . htmlspecialchars((string) $metric['tone'], ENT_QUOTES) . '">'
                . htmlspecialchars((string) $metric['trendDisplay'], ENT_QUOTES) . '</small></div>';
        }

        $periodLinks = '';
        foreach ($periods as $period) {
            $periodLinks .= '<a class="btn btn-sm ' . ($period['active'] ? 'btn-primary' : 'btn-default') . '" href="'
                . htmlspecialchars($period['url'], ENT_QUOTES) . '">' . $period['days'] . ' Tage</a>';
        }

        return '<div class="mai-seo-analytics-bar"><div class="mai-seo-analytics-metrics">' . $metrics . '</div><div class="mai-seo-analytics-actions">'
            . $periodLinks
            . '<a class="btn btn-sm btn-default" href="' . htmlspecialchars($moduleUrl, ENT_QUOTES) . '">Analytics</a>'
            . '</div></div>';
    }

    private function caption(string $key): string
    {
        return match ($key) {
            'visits' => 'Aufrufe',
            'bounce' => 'Absprungrate',
            'duration' => 'Verweildauer',
            'continuation' => 'Weiterklickrate',
            default => $key,
        };
    }
}
