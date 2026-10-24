<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Service;

/**
 * Whether a page has an explicit language overlay (not content-fallback).
 */
interface PageLanguageAvailability
{
    public function hasPageOverlay(int $pageId, int $languageId): bool;
}
