<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Analytics;

/**
 * Flächen-Liniendiagramm für Aufrufe und Besucher.
 */
final class TrafficChart
{
    /**
     * @param list<array{date: string, visits: int, visitors: int}> $series
     */
    public function render(array $series): string
    {
        $width = 720;
        $height = 248;
        $left = 44.0;
        $right = 16.0;
        $top = 18.0;
        $bottom = 40.0;
        $plotWidth = $width - $left - $right;
        $plotHeight = $height - $top - $bottom;
        $scaleMax = $this->niceMax($this->peak($series));
        $count = count($series);
        $visits = $this->points($series, 'visits', $left, $top, $plotWidth, $plotHeight, $scaleMax);
        $visitors = $this->points($series, 'visitors', $left, $top, $plotWidth, $plotHeight, $scaleMax);
        $baseline = round($top + $plotHeight, 1);

        $svg = '<svg class="mai-seo-traffic-svg" viewBox="0 0 ' . $width . ' ' . $height . '" role="img">';
        $svg .= '<title>Verlauf der Aufrufe und Besucher</title>';
        $svg .= $this->grid($left, $top, $plotWidth, $plotHeight, $scaleMax, $width - $right);
        $svg .= '<path class="mai-seo-area-visits" d="' . $this->area($visits, $baseline) . '"></path>';
        $svg .= '<path class="mai-seo-line-visits" d="' . $this->line($visits) . '"></path>';
        $svg .= '<path class="mai-seo-line-visitors" d="' . $this->line($visitors) . '"></path>';
        if ($count <= 16) {
            $svg .= $this->dots($visits, 'Aufrufe', 'mai-seo-dot-visits');
            $svg .= $this->dots($visitors, 'Besucher', 'mai-seo-dot-visitors');
        }
        $svg .= $this->dateLabels($series, $visits, $baseline + 18, $count);
        $svg .= '</svg>';

        return $svg;
    }

    /**
     * @param list<array{date: string, visits: int, visitors: int}> $series
     */
    private function peak(array $series): int
    {
        $peak = 1;
        foreach ($series as $point) {
            $peak = max($peak, $point['visits'], $point['visitors']);
        }

        return $peak;
    }

    private function niceMax(int $peak): int
    {
        if ($peak <= 4) {
            return $peak;
        }

        $padded = $peak * 1.08;
        $magnitude = 10 ** (int) floor(log10($padded));
        $normalized = $padded / $magnitude;
        $nice = match (true) {
            $normalized <= 1 => 1,
            $normalized <= 1.5 => 1.5,
            $normalized <= 2 => 2,
            $normalized <= 2.5 => 2.5,
            $normalized <= 4 => 4,
            $normalized <= 5 => 5,
            $normalized <= 6 => 6,
            $normalized <= 8 => 8,
            default => 10,
        };

        return (int) round($nice * $magnitude);
    }

    /**
     * @param list<array{date: string, visits: int, visitors: int}> $series
     * @return list<array{x: float, y: float, value: int}>
     */
    private function points(array $series, string $key, float $left, float $top, float $plotWidth, float $plotHeight, int $scaleMax): array
    {
        $count = count($series);
        $points = [];
        foreach ($series as $index => $point) {
            $value = (int) $point[$key];
            $x = $count <= 1 ? $left + $plotWidth / 2 : $left + ($index / ($count - 1)) * $plotWidth;
            $y = $top + $plotHeight - ($value / $scaleMax) * $plotHeight;
            $points[] = ['x' => round($x, 1), 'y' => round($y, 1), 'value' => $value];
        }

        return $points;
    }

    private function grid(float $left, float $top, float $plotWidth, float $plotHeight, int $scaleMax, float $rightEdge): string
    {
        $ticks = [0.0, $scaleMax / 2, (float) $scaleMax];
        $svg = '';
        foreach ($ticks as $tick) {
            $y = round($top + $plotHeight - ($tick / $scaleMax) * $plotHeight, 1);
            $label = abs($tick - round($tick)) < 0.01 ? (string) (int) round($tick) : number_format($tick, 1, ',', '');
            $svg .= '<line class="mai-seo-grid" x1="' . $left . '" y1="' . $y . '" x2="' . $rightEdge . '" y2="' . $y . '"></line>';
            $svg .= '<text class="mai-seo-axis" x="' . ($left - 8) . '" y="' . ($y + 4) . '" text-anchor="end">' . $label . '</text>';
        }

        return $svg;
    }

    /**
     * @param list<array{x: float, y: float, value: int}> $points
     */
    private function line(array $points): string
    {
        $commands = [];
        foreach ($points as $index => $point) {
            $commands[] = ($index === 0 ? 'M' : 'L') . $point['x'] . ' ' . $point['y'];
        }

        return implode(' ', $commands);
    }

    /**
     * @param list<array{x: float, y: float, value: int}> $points
     */
    private function area(array $points, float $baseline): string
    {
        if ($points === []) {
            return '';
        }

        $first = $points[0];
        $last = $points[array_key_last($points)];

        return $this->line($points) . ' L' . $last['x'] . ' ' . $baseline . ' L' . $first['x'] . ' ' . $baseline . ' Z';
    }

    /**
     * @param list<array{x: float, y: float, value: int}> $points
     */
    private function dots(array $points, string $caption, string $className): string
    {
        $svg = '';
        foreach ($points as $point) {
            $svg .= '<circle class="mai-seo-dot ' . $className . '" cx="' . $point['x'] . '" cy="' . $point['y'] . '" r="3.5"><title>'
                . htmlspecialchars($caption . ': ' . $point['value'], ENT_QUOTES | ENT_SUBSTITUTE) . '</title></circle>';
        }

        return $svg;
    }

    /**
     * @param list<array{date: string, visits: int, visitors: int}> $series
     * @param list<array{x: float, y: float, value: int}> $points
     */
    private function dateLabels(array $series, array $points, float $y, int $count): string
    {
        $svg = '';
        foreach ($series as $index => $point) {
            if (!$this->showLabel($index, $count)) {
                continue;
            }
            $svg .= '<text class="mai-seo-axis" x="' . $points[$index]['x'] . '" y="' . $y . '" text-anchor="middle">'
                . htmlspecialchars($this->shortDate((string) $point['date']), ENT_QUOTES | ENT_SUBSTITUTE) . '</text>';
        }

        return $svg;
    }

    private function showLabel(int $index, int $count): bool
    {
        if ($count <= 8) {
            return true;
        }

        $stride = $count <= 16 ? 2 : 5;

        if ($index === $count - 1) {
            return true;
        }

        return $index % $stride === 0 && ($count - 1) - $index >= $stride;
    }

    private function shortDate(string $date): string
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed instanceof \DateTimeImmutable ? $parsed->format('j.n.') : $date;
    }
}
