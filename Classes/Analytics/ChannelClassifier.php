<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Analytics;

final class ChannelClassifier
{
    public function classify(string $referrer, string $ownHost): string
    {
        $parts = parse_url($referrer);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $query = (string) ($parts['query'] ?? '');

        if ($host === '' || $this->isOwnHost($host, $ownHost)) {
            return 'direct';
        }

        if (preg_match('/(?:^|[?&])utm_[a-z]+=/i', $query) === 1) {
            return 'campaign';
        }

        if (preg_match('/(^|\.)(google|bing|duckduckgo|yahoo|ecosia|baidu|yandex)\./', $host) === 1) {
            return 'search';
        }

        if (preg_match('/(^|\.)(facebook|instagram|t\.co|twitter|x\.com|linkedin|youtube|tiktok|pinterest|xing)\./', $host) === 1
            || $host === 't.co' || $host === 'x.com') {
            return 'social';
        }

        return 'referral';
    }

    private function isOwnHost(string $host, string $ownHost): bool
    {
        $ownHost = strtolower($ownHost);
        if ($ownHost === '') {
            return false;
        }

        return $host === $ownHost || str_ends_with($host, '.' . $ownHost);
    }

    public function host(string $referrer): string
    {
        $host = parse_url($referrer, PHP_URL_HOST);

        return is_string($host) ? strtolower(substr($host, 0, 255)) : '';
    }
}
