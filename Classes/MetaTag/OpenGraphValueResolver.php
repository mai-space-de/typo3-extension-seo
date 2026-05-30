<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\MetaTag;

/**
 * Resolves Open Graph field values from page records with editor/core fallbacks.
 */
final class OpenGraphValueResolver
{
    private const OG_TYPE_MAP = [
        'Article' => 'article',
        'Person' => 'profile',
        'WebSite' => 'website',
        'Organization' => 'website',
        'LocalBusiness' => 'website',
        'Event' => 'website',
        'Product' => 'website',
        'JobPosting' => 'website',
        'WebPage' => 'website',
        'AboutPage' => 'website',
        'ContactPage' => 'website',
        'FAQPage' => 'website',
        'CollectionPage' => 'website',
        'BreadcrumbList' => 'website',
    ];

    /**
     * @param array<string, mixed> $pageRecord
     */
    public function resolveTitle(array $pageRecord, string $siteName = ''): string
    {
        $override = trim((string) ($pageRecord['tx_maiseo_og_title'] ?? ''));
        if ($override !== '') {
            return $override;
        }

        $title = trim((string) ($pageRecord['title'] ?? ''));
        if ($title !== '') {
            return $title;
        }

        return trim($siteName);
    }

    /**
     * @param array<string, mixed> $pageRecord
     */
    public function resolveDescription(array $pageRecord): string
    {
        $override = trim((string) ($pageRecord['tx_maiseo_og_description'] ?? ''));
        if ($override !== '') {
            return $override;
        }

        return trim((string) ($pageRecord['description'] ?? ''));
    }

    /**
     * @param array<string, mixed> $pageRecord
     */
    public function resolveOpenGraphType(array $pageRecord): string
    {
        $schemaType = trim((string) ($pageRecord['tx_maiseo_schema_type'] ?? ''));

        return self::OG_TYPE_MAP[$schemaType] ?? 'website';
    }

    /**
     * @param array<string, mixed> $pageRecord
     *
     * @return list<string> Field names to try in order for og:image resolution
     */
    public function resolveImageFieldCandidates(array $pageRecord): array
    {
        $candidates = [];

        if ((int) ($pageRecord['tx_maiseo_og_image'] ?? 0) > 0) {
            $candidates[] = 'tx_maiseo_og_image';
        }

        if ((int) ($pageRecord['media'] ?? 0) > 0) {
            $candidates[] = 'media';
        }

        return $candidates;
    }

    public function resolveTwitterCard(bool $hasImage): string
    {
        return $hasImage ? 'summary_large_image' : 'summary';
    }
}
