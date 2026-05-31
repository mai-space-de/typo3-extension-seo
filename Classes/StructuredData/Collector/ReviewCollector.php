<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\StructuredData\Collector;

use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;
use Maispace\MaiSeo\StructuredData\RecordStorageResolverInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

final class ReviewCollector implements CollectorInterface
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

        $qb = $this->connectionPool->getQueryBuilderForTable('tx_maitestimonials_testimonial');
        $rows = $qb
            ->select('uid', 'quote', 'author_name')
            ->from('tx_maitestimonials_testimonial')
            ->where(
                $qb->expr()->in(
                    'pid',
                    $qb->createNamedParameter($storagePids, Connection::PARAM_INT_ARRAY),
                ),
            )
            ->orderBy('sorting', 'ASC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAllAssociative();

        if ($rows === []) {
            return;
        }

        $row = $rows[0];

        if (!empty($row['quote'])) {
            $event->addToGraph('reviewBody', $row['quote']);
        }

        if (!empty($row['author_name'])) {
            $event->addToGraph('author', [
                '@type' => 'Person',
                'name' => $row['author_name'],
            ]);
        }
    }

    public function supportedTypes(): array
    {
        return ['Review'];
    }

    public function priority(): int
    {
        return 70;
    }
}
