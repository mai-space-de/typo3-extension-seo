<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\StructuredData\Collector;

use Maispace\MaiBase\Backend\CustomPageModule\DoktypeIds;
use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;
use Maispace\MaiSeo\StructuredData\RecordStorageResolverInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

final class NewsArticleCollector implements CollectorInterface
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly RecordStorageResolverInterface $recordStorageResolver,
    ) {}

    public function collect(StructuredDataCollectionEvent $event): void
    {
        if (!in_array($event->getGraph()['@type'] ?? '', $this->supportedTypes(), true)) {
            return;
        }

        if ($this->collectFromPageRecord($event)) {
            return;
        }

        $this->collectFromLegacyStorage($event);
    }

    public function supportedTypes(): array
    {
        return ['NewsArticle', 'Article'];
    }

    public function priority(): int
    {
        return 70;
    }

    private function collectFromPageRecord(StructuredDataCollectionEvent $event): bool
    {
        $record = $event->pageRecord;
        $doktype = (int) ($record['doktype'] ?? 0);
        $hasNewsFields = isset($record['tx_mainews_teaser']) || isset($record['tx_mainews_date']);

        if ($doktype !== DoktypeIds::NEWS && !$hasNewsFields) {
            return false;
        }

        $headline = trim((string) ($record['title'] ?? ''));
        if ($headline === '') {
            return false;
        }

        $event->addToGraph('headline', $headline);

        $teaser = trim((string) ($record['tx_mainews_teaser'] ?? ''));
        if ($teaser === '') {
            $teaser = trim((string) ($record['description'] ?? $record['abstract'] ?? ''));
        }
        if ($teaser !== '') {
            $event->addToGraph('description', $teaser);
        }

        $date = (int) ($record['tx_mainews_date'] ?? 0);
        if ($date > 0) {
            $event->addToGraph('datePublished', date('c', $date));
        }

        return true;
    }

    private function collectFromLegacyStorage(StructuredDataCollectionEvent $event): void
    {
        $storagePids = $this->recordStorageResolver->resolveStoragePids($event->pageUid);
        if ($storagePids === []) {
            return;
        }

        $qb = $this->connectionPool->getQueryBuilderForTable('tx_mainews_news');
        $rows = $qb
            ->select('uid', 'title', 'teaser', 'date')
            ->from('tx_mainews_news')
            ->where(
                $qb->expr()->in(
                    'pid',
                    $qb->createNamedParameter($storagePids, Connection::PARAM_INT_ARRAY),
                ),
            )
            ->orderBy('date', 'DESC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAllAssociative();

        if ($rows === []) {
            return;
        }

        $row = $rows[0];

        $event->addToGraph('headline', $row['title']);

        if (!empty($row['teaser'])) {
            $event->addToGraph('description', $row['teaser']);
        }

        if (!empty($row['date'])) {
            $event->addToGraph('datePublished', date('c', (int) $row['date']));
        }
    }
}
