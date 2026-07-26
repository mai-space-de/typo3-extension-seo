<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\StructuredData;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;

/**
 * Resolves PIDs where Extbase/domain records for a frontend page may be stored.
 */
final class RecordStorageResolver implements RecordStorageResolverInterface
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly SiteFinder $siteFinder,
    ) {}

    /**
     * @return list<int>
     */
    public function resolveStoragePids(int $pageUid): array
    {
        if ($pageUid <= 0) {
            return [];
        }

        $pids = [$pageUid];
        $rootPageId = $this->resolveSiteRootPageId($pageUid);
        if ($rootPageId > 0) {
            $pids = array_merge($pids, $this->fetchSysfolderPidsUnderRoot($rootPageId));
        }

        $pids = array_values(array_unique(array_filter($pids, static fn(int $pid): bool => $pid > 0)));

        return $pids;
    }

    private function resolveSiteRootPageId(int $pageUid): int
    {
        try {
            return $this->siteFinder->getSiteByPageId($pageUid)->getRootPageId();
        } catch (\Throwable) {
            try {
                $rootline = GeneralUtility::makeInstance(RootlineUtility::class, $pageUid)->get();

                return (int) ($rootline[0]['uid'] ?? $pageUid);
            } catch (\Throwable) {
                return $pageUid;
            }
        }
    }

    /**
     * @return list<int>
     */
    private function fetchSysfolderPidsUnderRoot(int $rootPageId): array
    {
        $sysfolderPids = [];
        $queue = [$rootPageId];
        $visited = [];

        while ($queue !== []) {
            $parentPid = array_shift($queue);
            if (isset($visited[$parentPid])) {
                continue;
            }
            $visited[$parentPid] = true;

            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
            $rows = $queryBuilder
                ->select('uid', 'doktype')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($parentPid, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('deleted', 0),
                )
                ->executeQuery()
                ->fetchAllAssociative();

            foreach ($rows as $row) {
                $uid = (int) ($row['uid'] ?? 0);
                if ($uid <= 0) {
                    continue;
                }

                if ((int) ($row['doktype'] ?? 0) === PageRepository::DOKTYPE_SYSFOLDER) {
                    $sysfolderPids[] = $uid;
                }

                $queue[] = $uid;
            }
        }

        return $sysfolderPids;
    }
}
