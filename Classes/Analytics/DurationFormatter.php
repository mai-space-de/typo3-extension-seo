<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Analytics;

final class DurationFormatter
{
    public function format(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . 's';
        }

        $minutes = intdiv($seconds, 60);
        $rest = $seconds % 60;

        return $rest === 0 ? $minutes . 'm' : $minutes . 'm ' . $rest . 's';
    }
}
