<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\StructuredData\Collector;

use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;
use Maispace\MaiSeo\StructuredData\RecordStorageResolverInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

final class JobPostingCollector implements CollectorInterface
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

        $storagePids = $this->recordStorageResolver->resolveStoragePids($event->pageUid);
        if ($storagePids === []) {
            return;
        }

        $qb = $this->connectionPool->getQueryBuilderForTable('tx_maijobs_job');
        $rows = $qb
            ->select('uid', 'title', 'description', 'deadline', 'status')
            ->from('tx_maijobs_job')
            ->where(
                $qb->expr()->in(
                    'pid',
                    $qb->createNamedParameter($storagePids, Connection::PARAM_INT_ARRAY),
                ),
                $qb->expr()->eq('status', $qb->createNamedParameter('open')),
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAllAssociative();

        if ($rows === []) {
            return;
        }

        $row = $rows[0];

        $event->addToGraph('title', $row['title']);

        if (!empty($row['description'])) {
            $event->addToGraph('description', $row['description']);
        }

        if (!empty($row['deadline']) && (int) $row['deadline'] > 0) {
            $event->addToGraph('validThrough', date('c', (int) $row['deadline']));
        }

        $event->addToGraph('directApply', true);
    }

    public function supportedTypes(): array
    {
        return ['JobPosting'];
    }

    public function priority(): int
    {
        return 70;
    }
}
