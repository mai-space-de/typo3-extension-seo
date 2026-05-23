<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Hook;

use Maispace\MaiSeo\Event\PageRecordSavedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;

/**
 * DataHandler hook that invalidates the maiseo_structured_data cache whenever
 * an extension record that contributes to structured-data output is saved.
 *
 * Watched tables map directly to the collectors registered in CollectorRegistry:
 *   tx_mainews_news        → NewsArticleCollector
 *   tx_maijobs_job         → JobPostingCollector
 *   tx_maiteam_team_member → PersonCollector
 *   tx_mailocations_location → PlaceCollector
 */
final class ExtensionRecordSaveHook
{
    /**
     * Extension record tables whose save events must invalidate the
     * structured-data cache of the page that owns the record.
     */
    private const WATCHED_TABLES = [
        'tx_mainews_news',
        'tx_maijobs_job',
        'tx_maiteam_team_member',
        'tx_mailocations_location',
    ];

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ConnectionPool $connectionPool,
    ) {}

    /**
     * Called by DataHandler after every INSERT or UPDATE on any table.
     * Resolves the owning page UID and dispatches PageRecordSavedEvent so the
     * existing PageRecordSavedListener invalidates the cache.
     */
    public function processDatamap_afterDatabaseOperations(
        string $status,
        string $table,
        int|string $id,
        array $fieldArray,
        DataHandler $dataHandler,
    ): void {
        if (!in_array($table, self::WATCHED_TABLES, true)) {
            return;
        }

        $pageUid = $this->resolvePageUid($status, $table, $id, $fieldArray);

        if ($pageUid === 0) {
            return;
        }

        $this->eventDispatcher->dispatch(new PageRecordSavedEvent($pageUid, [], $status));
    }

    /**
     * Resolves the pid (owning page UID) for a saved extension record.
     *
     * For new records the pid is always present in $fieldArray.
     * For updates the pid may be absent when it was not part of the change —
     * in that case we fall back to a lightweight DB lookup.
     */
    private function resolvePageUid(
        string $status,
        string $table,
        int|string $id,
        array $fieldArray,
    ): int {
        if ($status === 'new') {
            return (int) ($fieldArray['pid'] ?? 0);
        }

        // Updates: pid is present in fieldArray only when the record was moved.
        if (isset($fieldArray['pid'])) {
            return (int) $fieldArray['pid'];
        }

        $recordUid = (int) $id;
        if ($recordUid === 0) {
            return 0;
        }

        return $this->fetchPidForRecord($table, $recordUid);
    }

    private function fetchPidForRecord(string $table, int $uid): int
    {
        $qb = $this->connectionPool->getQueryBuilderForTable($table);
        $row = $qb
            ->select('pid')
            ->from($table)
            ->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative();

        return $row !== false ? (int) $row['pid'] : 0;
    }
}
