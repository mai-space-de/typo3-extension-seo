<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Analytics;

final class Sparkline
{
    /**
     * @param list<int> $values
     */
    public function render(array $values, int $width = 120, int $height = 36): string
    {
        if ($values === []) {
            return '';
        }

        $max = max($values);
        $min = min($values);
        $span = max(1, $max - $min);
        $last = count($values) - 1;
        $coords = [];
        foreach ($values as $index => $value) {
            $x = $last === 0 ? $width / 2 : ($index / $last) * ($width - 4) + 2;
            $y = ($height - 4) - (($value - $min) / $span) * ($height - 8) + 2;
            $coords[] = [round($x, 1), round($y, 1)];
        }

        $line = [];
        foreach ($coords as $index => $coord) {
            $line[] = ($index === 0 ? 'M' : 'L') . $coord[0] . ' ' . $coord[1];
        }
        $first = $coords[0];
        $end = $coords[$last];
        $area = implode(' ', $line) . ' L' . $end[0] . ' ' . ($height - 1) . ' L' . $first[0] . ' ' . ($height - 1) . ' Z';

        return '<svg class="mai-seo-spark" viewBox="0 0 ' . $width . ' ' . $height . '" aria-hidden="true">'
            . '<path class="mai-seo-spark-area" d="' . $area . '"></path>'
            . '<path class="mai-seo-spark-line" d="' . implode(' ', $line) . '"></path>'
            . '</svg>';
    }
}
