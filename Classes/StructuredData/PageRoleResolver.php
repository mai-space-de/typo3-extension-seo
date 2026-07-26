<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\StructuredData;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * Derives schema.org @type values from page context, layout role, and content elements.
 */
final class PageRoleResolver
{
    /**
     * Plugin CType → schema.org types (aligned with scripts/quality-audit/audit-seo-jsonld.py).
     *
     * @var array<string, list<string>>
     */
    private const PLUGIN_TYPES = [
        'maifaq_list' => ['FAQPage'],
        'maispace_faq_list' => ['FAQPage'],
        'maijobs_detail' => ['JobPosting'],
        'maijobs_list' => ['CollectionPage', 'WebPage'],
        'mailocations_detail' => ['Place', 'LocalBusiness'],
        'mailocations_list' => ['CollectionPage', 'WebPage'],
        'maiteam_view' => ['Person'],
        'mainews_list' => ['CollectionPage', 'WebPage'],
        'mainews_rss' => ['WebPage'],
        'maigallery_gallery' => ['CollectionPage', 'WebPage'],
        'maimember_view' => ['CollectionPage', 'WebPage'],
        'maimember_application' => ['WebPage'],
        'maitestimonials_list' => ['CollectionPage', 'WebPage'],
        'maitestimonials_single' => ['Review'],
        'maitestimonials_slider' => ['CollectionPage', 'WebPage'],
        'maievents_registration' => ['WebPage'],
        'maicanteen_week' => ['WebPage'],
        'maicanteen_print' => ['WebPage'],
        'maiaccount_account' => ['WebPage'],
        'maiaccount_register' => ['WebPage'],
        'maiaccount_mfa' => ['WebPage'],
        'maiaccount_stories' => ['WebPage'],
        'mainewsletter_newsletter' => ['WebPage'],
        'maisearch_search' => ['WebPage'],
        'maisurvey_survey' => ['WebPage'],
        'mai_timeline_list' => ['CollectionPage', 'WebPage'],
    ];

    /**
     * Theme CType → schema.org types.
     *
     * @var array<string, list<string>>
     */
    private const CTYPE_TYPES = [
        'maispace_faq' => ['FAQPage'],
        'maispace_map' => ['Place', 'LocalBusiness'],
        'maispace_contactinfo' => ['LocalBusiness', 'ContactPage'],
        'mai_events_view' => ['Event'],
        'maispace_form' => ['WebPage'],
    ];

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly SiteFinder $siteFinder,
    ) {}

    /**
     * @return list<string>
     */
    public function resolve(int $pageUid, array $pageRecord): array
    {
        $types = ['WebPage'];

        if ($this->isHomepage($pageUid)) {
            $types = array_merge($types, ['Organization', 'WebSite']);
        }

        foreach ($this->fetchContentElementTypes($pageUid) as $contentType) {
            if (isset(self::PLUGIN_TYPES[$contentType])) {
                $types = array_merge($types, self::PLUGIN_TYPES[$contentType]);
                continue;
            }

            if (isset(self::CTYPE_TYPES[$contentType])) {
                $types = array_merge($types, self::CTYPE_TYPES[$contentType]);
            }
        }

        $manualType = trim((string) ($pageRecord['tx_maiseo_schema_type'] ?? ''));
        if ($manualType !== '' && !in_array($manualType, $types, true)) {
            array_unshift($types, $manualType);
        }

        return $this->uniqueTypes($types);
    }

    private function isHomepage(int $pageUid): bool
    {
        try {
            return $this->siteFinder->getSiteByPageId($pageUid)->getRootPageId() === $pageUid;
        } catch (\Throwable) {
            return $pageUid === 1;
        }
    }

    /**
     * @return list<string>
     */
    private function fetchContentElementTypes(int $pageUid): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('CType')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageUid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', 0),
            )
            ->executeQuery()
            ->fetchAllAssociative();

        $types = [];
        foreach ($rows as $row) {
            $cType = (string) ($row['CType'] ?? '');
            if ($cType !== '') {
                $types[] = $cType;
            }
        }

        return $types;
    }

    /**
     * @param list<string> $types
     *
     * @return list<string>
     */
    private function uniqueTypes(array $types): array
    {
        $unique = [];
        foreach ($types as $type) {
            if ($type !== '' && !in_array($type, $unique, true)) {
                $unique[] = $type;
            }
        }

        return $unique;
    }
}
