<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Service;

use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

/**
 * Picks the best matching site language for an HTTP Accept-Language header.
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

        foreach ($this->parseAcceptLanguage($acceptLanguageHeader) as $tag) {
            $match = $this->matchTag($tag, $candidates);
            if ($match instanceof SiteLanguage) {
                return $match;
            }
        }

        return null;
    }

    /**
     * @return list<string> Language tags ordered by descending quality
     */
    public function parseAcceptLanguage(string $header): array
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

        return array_map(static fn(array $item): string => $item['tag'], $weighted);
    }

    /**
     * @param list<SiteLanguage> $languages
     */
    private function matchTag(string $tag, array $languages): ?SiteLanguage
    {
        if ($tag === '*') {
            return $languages[0] ?? null;
        }

        $normalizedTag = strtolower(str_replace('_', '-', $tag));

        foreach ($languages as $language) {
            $hreflang = strtolower(str_replace('_', '-', $language->getHreflang()));
            if ($hreflang !== '' && $hreflang === $normalizedTag) {
                return $language;
            }
        }

        foreach ($languages as $language) {
            $localeName = strtolower(str_replace('_', '-', $language->getLocale()->getName()));
            if ($localeName !== '' && $localeName === $normalizedTag) {
                return $language;
            }
        }

        $primary = explode('-', $normalizedTag, 2)[0];
        if ($primary === '') {
            return null;
        }

        foreach ($languages as $language) {
            $languageCode = strtolower($language->getLocale()->getLanguageCode());
            if ($languageCode === $primary) {
                return $language;
            }
        }

        foreach ($languages as $language) {
            $hreflangPrimary = explode('-', strtolower(str_replace('_', '-', $language->getHreflang())), 2)[0];
            if ($hreflangPrimary === $primary) {
                return $language;
            }
        }

        return null;
    }
}
