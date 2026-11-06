<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Analytics;

final class SeriesFiller
{
    /**
     * @param list<array{day_index: int, visits: int, visitors: int}> $rows
     * @return list<array{date: string, visits: int, visitors: int, height: int}>
     */
    public function fill(array $rows, int $days, int $now): array
    {
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[$row['day_index']] = $row;
        }

        $points = [];
        $max = 0;
        for ($i = $days - 1; $i >= 0; $i--) {
            $index = (int) floor(($now - $i * 86400) / 86400);
            $visits = (int) ($indexed[$index]['visits'] ?? 0);
            $max = max($max, $visits);
            $points[] = [
                'date' => gmdate('Y-m-d', $index * 86400),
                'visits' => $visits,
                'visitors' => (int) ($indexed[$index]['visitors'] ?? 0),
            ];
        }

        $series = [];
        foreach ($points as $point) {
            $point['height'] = $max > 0 && $point['visits'] > 0
                ? (int) max(4, round($point['visits'] / $max * 100))
                : 0;
            $series[] = $point;
        }

        return $series;
    }
}
