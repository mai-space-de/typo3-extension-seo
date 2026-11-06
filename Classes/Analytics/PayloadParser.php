<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Analytics;

final class PayloadParser
{
    private const MAX_BYTES = 8192;

    public function parse(string $raw): ?HitDraft
    {
        if ($raw === '' || strlen($raw) > self::MAX_BYTES) {
            return null;
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return null;
        }

        $pageId = (int) ($data['pageId'] ?? 0);
        $token = (string) ($data['hitToken'] ?? '');
        $path = (string) ($data['path'] ?? '');
        if ($pageId <= 0 || preg_match('/^[a-f0-9-]{16,36}$/i', $token) !== 1 || !str_starts_with($path, '/')) {
            return null;
        }

        return new HitDraft(
            pageId: $pageId,
            languageId: max(0, (int) ($data['languageId'] ?? 0)),
            locale: $this->cut((string) ($data['locale'] ?? ''), 35),
            path: $this->cut($path, 512),
            title: $this->cut(trim(strip_tags((string) ($data['title'] ?? ''))), 255),
            referrer: $this->cut((string) ($data['referrer'] ?? ''), 1024),
            screenWidth: max(0, (int) ($data['screen'] ?? 0)),
            hitToken: strtolower($token),
            duration: min(1800, max(0, (int) ($data['duration'] ?? 0))),
        );
    }

    private function cut(string $value, int $length): string
    {
        if (strlen($value) <= $length) {
            return $value;
        }

        return substr($value, 0, $length);
    }
}
