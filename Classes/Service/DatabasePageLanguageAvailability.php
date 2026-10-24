<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Service;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;

/**
 * Reads `pages` overlays to decide if a language URL would resolve without
 * falling back to the default language (true `strict` site languages).
 */
final class DatabasePageLanguageAvailability implements PageLanguageAvailability
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function hasPageOverlay(int $pageId, int $languageId): bool
    {
        if ($pageId <= 0) {
            return false;
        }

        if ($languageId <= 0) {
            return true;
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction())
            ->add(new HiddenRestriction());

        $count = $queryBuilder
            ->count('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    'l10n_parent',
                    $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT),
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($languageId, Connection::PARAM_INT),
                ),
            )
            ->executeQuery()
            ->fetchOne();

        return (int) $count > 0;
    }
}
