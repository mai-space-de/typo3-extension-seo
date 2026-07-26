<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Service;

use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

/**
 * Picks the best matching site language for an HTTP Accept-Language header.
 *
 * Languages are scored by the highest matching tag quality. Equal scores break
 * in site language order (default language first), so headers like `en,de`
 * (both q=1.0) stay on German instead of redirecting solely because English
 * appears first — a common mobile / browser preference-list quirk.
 */
final class AcceptLanguageNegotiator
{
    /**
     * @param list<SiteLanguage> $languages
     */
    public function negotiate(string $acceptLanguageHeader, array $languages): ?SiteLanguage
    {
        $candidates = array_values(array_filter(
            $languages,
            static fn(SiteLanguage $language): bool => $language->isEnabled(),
        ));

        if ($candidates === [] || trim($acceptLanguageHeader) === '') {
            return null;
        }

        $weightedTags = $this->parseAcceptLanguageWeighted($acceptLanguageHeader);
        if ($weightedTags === []) {
            return null;
        }

        $bestLanguage = null;
        $bestScore = 0.0;

        foreach ($candidates as $language) {
            $score = $this->scoreLanguage($language, $weightedTags);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestLanguage = $language;
            }
        }

        return $bestLanguage;
    }

    /**
     * @return list<string> Language tags ordered by descending quality
     */
    public function parseAcceptLanguage(string $header): array
    {
        return array_map(
            static fn(array $item): string => $item['tag'],
            $this->parseAcceptLanguageWeighted($header),
        );
    }

    /**
     * @return list<array{tag: string, quality: float, index: int}>
     */
    private function parseAcceptLanguageWeighted(string $header): array
    {
        $parts = explode(',', $header);
        $weighted = [];

        foreach ($parts as $index => $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $quality = 1.0;
            $tag = $part;
            if (str_contains($part, ';')) {
                [$tag, $params] = explode(';', $part, 2);
                $tag = trim($tag);
                if (preg_match('/q\s*=\s*([0-9.]+)/i', $params, $matches) === 1) {
                    $quality = (float) $matches[1];
                }
            }

            if ($tag === '' || $quality <= 0.0) {
                continue;
            }

            $weighted[] = [
                'tag' => strtolower($tag),
                'quality' => $quality,
                'index' => $index,
            ];
        }

        usort(
            $weighted,
            static function (array $left, array $right): int {
                if ($left['quality'] === $right['quality']) {
                    return $left['index'] <=> $right['index'];
                }

                return $right['quality'] <=> $left['quality'];
            },
        );

        return $weighted;
    }

    /**
     * @param list<array{tag: string, quality: float, index: int}> $weightedTags
     */
    private function scoreLanguage(SiteLanguage $language, array $weightedTags): float
    {
        $best = 0.0;

        foreach ($weightedTags as $item) {
            if ($this->languageMatchesTag($language, $item['tag'])) {
                $best = max($best, $item['quality']);
            }
        }

        return $best;
    }

    private function languageMatchesTag(SiteLanguage $language, string $tag): bool
    {
        if ($tag === '*') {
            return true;
        }

        $normalizedTag = strtolower(str_replace('_', '-', $tag));

        $hreflang = strtolower(str_replace('_', '-', $language->getHreflang()));
        if ($hreflang !== '' && $hreflang === $normalizedTag) {
            return true;
        }

        $localeName = strtolower(str_replace('_', '-', $language->getLocale()->getName()));
        if ($localeName !== '' && $localeName === $normalizedTag) {
            return true;
        }

        $primary = explode('-', $normalizedTag, 2)[0];
        if ($primary === '') {
            return false;
        }

        $languageCode = strtolower($language->getLocale()->getLanguageCode());
        if ($languageCode === $primary) {
            return true;
        }

        $hreflangPrimary = explode('-', $hreflang, 2)[0];

        return $hreflangPrimary === $primary;
    }
}
