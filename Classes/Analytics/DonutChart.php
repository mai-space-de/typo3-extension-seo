<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Analytics;

/**
 * Donut für Geräte, Browser und ähnliche Anteile.
 */
final class DonutChart
{
    /**
     * @param list<array{label: string, count: int, percent: float, color: string}> $rows
     */
    public function render(array $rows): string
    {
        $total = 0;
        foreach ($rows as $row) {
            $total += $row['count'];
        }

        $svg = '<svg class="mai-seo-donut-svg" viewBox="0 0 120 120" role="img">';
        $svg .= '<title>Anteile</title>';
        $svg .= '<g transform="rotate(-90 60 60)">';
        if ($total === 0) {
            $svg .= '<circle class="mai-seo-donut-empty" cx="60" cy="60" r="42"></circle>';
        } else {
            $svg .= $this->segments($rows, $total);
        }
        $svg .= '</g>';
        $svg .= '<text class="mai-seo-donut-total" x="60" y="58" text-anchor="middle">' . $total . '</text>';
        $svg .= '<text class="mai-seo-donut-caption" x="60" y="72" text-anchor="middle">gesamt</text>';
        $svg .= '</svg>';

        return $svg;
    }

    /**
     * @param list<array{label: string, count: int, percent: float, color: string}> $rows
     */
    private function segments(array $rows, int $total): string
    {
        $radius = 42.0;
        $circumference = 2 * M_PI * $radius;
        $gap = count($rows) > 1 ? 2.0 : 0.0;
        $offset = 0.0;
        $svg = '';
        foreach ($rows as $row) {
            $length = max(0.0, ($row['count'] / $total) * $circumference - $gap);
            $svg .= '<circle cx="60" cy="60" r="42" fill="none" stroke="'
                . htmlspecialchars($row['color'], ENT_QUOTES | ENT_SUBSTITUTE)
                . '" stroke-width="14" stroke-dasharray="' . round($length, 2) . ' ' . round($circumference, 2)
                . '" stroke-dashoffset="' . round(-$offset, 2) . '"><title>'
                . htmlspecialchars($row['label'] . ': ' . $row['count'], ENT_QUOTES | ENT_SUBSTITUTE)
                . '</title></circle>';
            $offset += $length + $gap;
        }

        return $svg;
    }
}
