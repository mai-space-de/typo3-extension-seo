<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Analytics;

/**
 * Verlaufsdiagramm im Stil der TYPO3-Analytics-Widgets:
 * geglättete Linien, Flächenfüllung und zwei Achsen.
 */
final class TrafficChart
{
    private const WIDTH = 100.0;
    private const HEIGHT = 32.0;
    private const PADDING = 2.0;

    private static int $clipId = 0;

    /**
     * @param list<array{date: string, visits: int, visitors: int}> $series
     */
    public function render(array $series): string
    {
        if ($series === []) {
            return '';
        }

        $visits = array_map(static fn (array $point): int => $point['visits'], $series);
        $visitors = array_map(static fn (array $point): int => $point['visitors'], $series);
        $visitMax = $this->niceMax($this->peak($visits));
        $visitorMax = $this->niceMax($this->peak($visitors));
        $visitPoints = $this->points($visits, $visitMax);
        $visitorPoints = $this->points($visitors, $visitorMax);
        $id = 'mai-seo-traffic-' . (++self::$clipId);
        $baseline = self::HEIGHT - self::PADDING;

        $svg = '<div class="tx-analytics-traffic-graph-chart-wrap tx-analytics-traffic-graph-chart-wrap--dual-axis">';
        $svg .= $this->axis($this->ticks($visitMax), 'Aufrufe', false);
        $svg .= '<div class="tx-analytics-traffic-graph-chart-area"><div class="tx-analytics-sparkline tx-analytics-traffic-graph-sparkline">';
        $svg .= '<svg class="tx-analytics-sparkline-svg" viewBox="0 0 ' . self::WIDTH . ' ' . self::HEIGHT . '" preserveAspectRatio="none" role="img" aria-hidden="true" focusable="false" data-tones="visits,visitors" data-keys="visits,visitors">';
        $svg .= '<defs><clipPath id="' . $id . '"><rect x="0" y="0" width="' . self::WIDTH . '" height="' . $baseline . '"></rect></clipPath>';
        $svg .= $this->gradient($id . '-visits', '--tx-analytics-color-visits', $baseline);
        $svg .= $this->gradient($id . '-visitors', '--tx-analytics-color-visitors', $baseline);
        $svg .= '</defs>';
        $svg .= $this->grid($visitMax);
        $svg .= '<g clip-path="url(#' . $id . ')">';
        $svg .= '<path class="tx-analytics-sparkline-fill" data-tone="visits" data-dataset-key="visits" style="fill:url(#' . $id . '-visits)" d="' . $this->fill($visitPoints) . '"></path>';
        $svg .= '<path class="tx-analytics-sparkline-fill" data-tone="visitors" data-dataset-key="visitors" style="fill:url(#' . $id . '-visitors)" d="' . $this->fill($visitorPoints) . '"></path>';
        $svg .= '<path class="tx-analytics-sparkline-line" data-tone="visits" data-dataset-key="visits" d="' . $this->line($visitPoints) . '"><title>Aufrufe: ' . $visits[array_key_last($visits)] . '</title></path>';
        $svg .= '<path class="tx-analytics-sparkline-line" data-tone="visitors" data-dataset-key="visitors" d="' . $this->line($visitorPoints) . '"><title>Besucher: ' . $visitors[array_key_last($visitors)] . '</title></path>';
        $svg .= '<line class="tx-analytics-sparkline-hover-line" x1="-1" y1="' . self::PADDING . '" x2="-1" y2="' . $baseline . '" visibility="hidden" pointer-events="none"></line>';
        $svg .= '</g>';
        $svg .= $this->hoverRects($series, $visitPoints, $visitorPoints);
        $svg .= '</svg></div></div>';
        $svg .= $this->axis($this->ticks($visitorMax), 'Besucher', true);
        $svg .= '<div class="tx-analytics-traffic-graph-x-axis" aria-hidden="true">' . $this->dateLabels($series) . '</div>';
        $svg .= '</div>';

        return $svg;
    }

    /**
     * @param list<int> $values
     */
    private function peak(array $values): int
    {
        return max(1, ...$values);
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
     * @return list<int>
     */
    private function ticks(int $max): array
    {
        return [$max, (int) round($max * 2 / 3), (int) round($max / 3), 0];
    }

    /**
     * @param list<int> $ticks
     */
    private function axis(array $ticks, string $title, bool $right): string
    {
        $class = 'tx-analytics-traffic-graph-y-axis' . ($right ? ' tx-analytics-traffic-graph-y-axis--right' : '');
        $html = '<div class="' . $class . '" aria-hidden="true">';
        foreach ($ticks as $tick) {
            $html .= '<span class="tx-analytics-traffic-graph-y-label">' . $this->axisLabel($tick) . '</span>';
        }
        $html .= '<span class="tx-analytics-traffic-graph-axis-title">' . $title . '</span></div>';

        return $html;
    }

    private function axisLabel(int $value): string
    {
        if ($value >= 1000) {
            return rtrim(rtrim(number_format($value / 1000, 1, '.', ''), '0'), '.') . 'k';
        }

        return (string) $value;
    }

    /**
     * @param list<int> $values
     * @return list<array{0: float, 1: float}>
     */
    private function points(array $values, int $max): array
    {
        $count = count($values);
        $usable = self::HEIGHT - (self::PADDING * 2);
        $step = $count > 1 ? self::WIDTH / ($count - 1) : 0.0;
        $points = [];
        foreach ($values as $index => $value) {
            $x = $count > 1 ? $index * $step : self::WIDTH / 2;
            $y = self::PADDING + (($max - $value) / max(1, $max)) * $usable;
            $points[] = [$x, $y];
        }

        return $points;
    }

    private function grid(int $max): string
    {
        $html = '';
        foreach ($this->ticks($max) as $tick) {
            $y = $this->format($this->points([$tick], $max)[0][1]);
            $html .= '<line class="tx-analytics-sparkline-grid-line" x1="0" y1="' . $y . '" x2="' . self::WIDTH . '" y2="' . $y . '"></line>';
        }

        return $html;
    }

    /**
     * @param list<array{0: float, 1: float}> $points
     */
    private function line(array $points): string
    {
        $count = count($points);
        if ($count < 3) {
            return $this->straight($points);
        }

        $path = 'M' . $this->format($points[0][0]) . ' ' . $this->format($points[0][1]);
        $low = self::PADDING;
        $high = self::HEIGHT - self::PADDING;
        for ($index = 0; $index < $count - 1; $index++) {
            $p0 = $index > 0 ? $points[$index - 1] : $points[0];
            $p1 = $points[$index];
            $p2 = $points[$index + 1];
            $p3 = $index + 2 < $count ? $points[$index + 2] : $points[$count - 1];
            $cp1y = min($high, max($low, $p1[1] + ($p2[1] - $p0[1]) / 6));
            $cp2y = min($high, max($low, $p2[1] - ($p3[1] - $p1[1]) / 6));
            $path .= ' C' . $this->format($p1[0] + ($p2[0] - $p0[0]) / 6) . ' ' . $this->format($cp1y)
                . ',' . $this->format($p2[0] - ($p3[0] - $p1[0]) / 6) . ' ' . $this->format($cp2y)
                . ',' . $this->format($p2[0]) . ' ' . $this->format($p2[1]);
        }

        return $path;
    }

    /**
     * @param list<array{0: float, 1: float}> $points
     */
    private function straight(array $points): string
    {
        $path = [];
        foreach ($points as $index => $point) {
            $path[] = ($index === 0 ? 'M' : 'L') . $this->format($point[0]) . ' ' . $this->format($point[1]);
        }

        return implode(' ', $path);
    }

    /**
     * @param list<array{0: float, 1: float}> $points
     */
    private function fill(array $points): string
    {
        $baseline = $this->format(self::HEIGHT - self::PADDING);
        $first = $points[0];
        $last = $points[array_key_last($points)];

        return $this->line($points)
            . ' L' . $this->format($last[0]) . ' ' . $baseline
            . ' L' . $this->format($first[0]) . ' ' . $baseline
            . ' Z';
    }

    private function gradient(string $id, string $variable, float $baseline): string
    {
        return '<linearGradient id="' . $id . '" x1="0" y1="' . self::PADDING . '" x2="0" y2="' . $baseline . '" gradientUnits="userSpaceOnUse">'
            . '<stop offset="0%" style="stop-color:color-mix(in srgb, var(' . $variable . '), white 38%);stop-opacity:.68"></stop>'
            . '<stop offset="24%" style="stop-color:color-mix(in srgb, var(' . $variable . '), white 58%);stop-opacity:.26"></stop>'
            . '<stop offset="100%" style="stop-color:var(' . $variable . ');stop-opacity:0"></stop>'
            . '</linearGradient>';
    }

    /**
     * @param list<array{date: string, visits: int, visitors: int}> $series
     * @param list<array{0: float, 1: float}> $visitPoints
     * @param list<array{0: float, 1: float}> $visitorPoints
     */
    private function hoverRects(array $series, array $visitPoints, array $visitorPoints): string
    {
        $count = count($visitPoints);
        $step = $count > 1 ? self::WIDTH / ($count - 1) : self::WIDTH;
        $html = '';
        foreach ($series as $index => $point) {
            $x = $visitPoints[$index][0];
            $rectX = max(0.0, $x - $step / 2);
            $rectWidth = min(self::WIDTH - $rectX, $step);
            $payload = htmlspecialchars(json_encode([
                'date' => $this->fullDate((string) $point['date']),
                'metrics' => [
                    ['tone' => 'visits', 'label' => 'Aufrufe', 'value' => (string) $point['visits']],
                    ['tone' => 'visitors', 'label' => 'Besucher', 'value' => (string) $point['visitors']],
                ],
            ], JSON_THROW_ON_ERROR), ENT_QUOTES | ENT_SUBSTITUTE);
            $html .= '<rect class="tx-analytics-sparkline-point-tooltip" x="' . $this->format($rectX) . '" y="0" width="' . $this->format($rectWidth) . '" height="' . self::HEIGHT . '" fill="transparent" data-tooltip="' . $payload . '" data-x="' . $this->format($x) . '" data-y-0="' . $this->format($visitPoints[$index][1]) . '" data-y-1="' . $this->format($visitorPoints[$index][1]) . '"></rect>';
        }

        return $html;
    }

    /**
     * @param list<array{date: string, visits: int, visitors: int}> $series
     */
    private function dateLabels(array $series): string
    {
        $count = count($series);
        $html = '';
        foreach ($series as $index => $point) {
            if (!$this->showLabel($index, $count)) {
                continue;
            }
            $percent = $count <= 1 ? 50 : round($index / ($count - 1) * 100, 2);
            $html .= '<span style="--x-pct:' . $percent . '%">' . htmlspecialchars($this->shortDate((string) $point['date']), ENT_QUOTES | ENT_SUBSTITUTE) . '</span>';
        }

        return $html;
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

    private function fullDate(string $date): string
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed instanceof \DateTimeImmutable ? $parsed->format('d.m.Y') : $date;
    }

    private function format(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
