<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Analytics;

/**
 * Ringdiagramm im Stil der TYPO3-Analytics-Widgets.
 */
final class DonutChart
{
    /**
     * @param list<array{label: string, count: int, percent: float, tone?: string, color?: string}> $rows
     */
    public function render(array $rows): string
    {
        $total = 0;
        foreach ($rows as $row) {
            $total += $row['count'];
        }

        $svg = '<svg viewBox="0 0 100 100" role="img" aria-hidden="true">';
        if ($total === 0) {
            $svg .= '<circle class="mai-seo-donut-empty tx-analytics-traffic-sources-donut-track" cx="50" cy="50" r="40" fill="none" stroke-width="18"></circle>';
            $svg .= '</svg>';

            return $svg;
        }

        $svg .= '<circle class="tx-analytics-traffic-sources-donut-track" cx="50" cy="50" r="40" fill="none" stroke-width="18"></circle>';
        $circumference = 2 * M_PI * 40;
        $offset = 0.0;
        foreach ($rows as $row) {
            if ($row['count'] <= 0) {
                continue;
            }
            $length = ($row['count'] / $total) * $circumference;
            $rotation = -90 + ($offset / $circumference) * 360;
            $tone = $this->tone((string) ($row['tone'] ?? 'series-1'));
            $svg .= '<circle class="tx-analytics-traffic-sources-donut-segment tx-analytics-traffic-sources-tone-' . $tone . '" cx="50" cy="50" r="40" fill="none" stroke-width="18" stroke-dasharray="'
                . round($length, 3) . ' ' . round($circumference, 3) . '" transform="rotate(' . round($rotation, 3) . ' 50 50)"><title>'
                . htmlspecialchars($row['label'] . ': ' . $row['count'], ENT_QUOTES | ENT_SUBSTITUTE)
                . '</title></circle>';
            $offset += $length;
        }
        $svg .= '</svg>';

        return $svg;
    }

    private function tone(string $tone): string
    {
        return preg_match('/^(?:source-(?:direct|search|social|email|paid|ai|unknown|other)|device-(?:desktop|mobile|tablet|phone|unknown)|series-(?:[1-8]|other))$/', $tone) === 1
            ? $tone
            : 'series-1';
    }
}
