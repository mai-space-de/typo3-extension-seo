<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Analytics;

final class ClientClassifier
{
    public function device(int $screenWidth, string $userAgent): string
    {
        if ($screenWidth > 0) {
            return match (true) {
                $screenWidth < 768 => 'mobile',
                $screenWidth < 1024 => 'tablet',
                default => 'desktop',
            };
        }

        $agent = strtolower($userAgent);
        if (str_contains($agent, 'ipad') || str_contains($agent, 'tablet')) {
            return 'tablet';
        }
        if (preg_match('/mobile|iphone|android/', $agent) === 1) {
            return 'mobile';
        }

        return 'desktop';
    }

    public function browser(string $userAgent): string
    {
        $agent = strtolower($userAgent);

        return match (true) {
            str_contains($agent, 'edg/') => 'Edge',
            str_contains($agent, 'opr/') || str_contains($agent, 'opera') => 'Opera',
            str_contains($agent, 'firefox') => 'Firefox',
            str_contains($agent, 'chrome') || str_contains($agent, 'crios') => 'Chrome',
            str_contains($agent, 'safari') => 'Safari',
            default => 'Sonstige',
        };
    }
}
