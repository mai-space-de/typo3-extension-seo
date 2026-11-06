<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Analytics;

final readonly class HitDraft
{
    public function __construct(
        public int $pageId,
        public int $languageId,
        public string $locale,
        public string $path,
        public string $title,
        public string $referrer,
        public int $screenWidth,
        public string $hitToken,
        public int $duration,
    ) {}
}
