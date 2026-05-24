<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\StructuredData\Collector;

use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

final class EventCollector implements CollectorInterface
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function collect(StructuredDataCollectionEvent $event): void
    {
        if (!in_array($event->getGraph()['@type'] ?? '', $this->supportedTypes(), true)) {
            return;
        }

        $qb = $this->connectionPool->getQueryBuilderForTable('tx_maievents_event');
        $rows = $qb
            ->select('uid', 'title', 'description', 'location', 'start_date', 'end_date')
            ->from('tx_maievents_event')
            ->where($qb->expr()->eq('pid', $qb->createNamedParameter($event->pageUid, Connection::PARAM_INT)))
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAllAssociative();

        if ($rows === []) {
            return;
        }

        $row = $rows[0];

        if (!empty($row['title'])) {
            $event->addToGraph('name', $row['title']);
        }

        if (!empty($row['description'])) {
            $event->addToGraph('description', $row['description']);
        }

        if (!empty($row['start_date']) && (int) $row['start_date'] > 0) {
            $event->addToGraph('startDate', date('c', (int) $row['start_date']));
        }

        if (!empty($row['end_date']) && (int) $row['end_date'] > 0) {
            $event->addToGraph('endDate', date('c', (int) $row['end_date']));
        }

        if (!empty($row['location'])) {
            $event->addToGraph('location', [
                '@type' => 'Place',
                'name' => $row['location'],
            ]);
        }

        $event->addToGraph('eventStatus', 'https://schema.org/EventScheduled');
    }

    public function supportedTypes(): array
    {
        return ['Event'];
    }

    public function priority(): int
    {
        return 70;
    }
}
