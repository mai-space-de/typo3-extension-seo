<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\StructuredData\Collector;

use Maispace\MaiBase\Backend\CustomPageModule\DoktypeIds;
use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;
use Maispace\MaiSeo\StructuredData\RecordStorageResolverInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

final class EventCollector implements CollectorInterface
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
        return ['Event'];
    }

    public function priority(): int
    {
        return 70;
    }

    private function collectFromPageRecord(StructuredDataCollectionEvent $event): bool
    {
        $record = $event->pageRecord;
        $doktype = (int) ($record['doktype'] ?? 0);
        $hasEventFields = isset($record['tx_maievents_start_date']) || isset($record['tx_maievents_location']);

        if ($doktype !== DoktypeIds::EVENT && !$hasEventFields) {
            return false;
        }

        $name = trim((string) ($record['title'] ?? ''));
        if ($name === '') {
            return false;
        }

        $event->addToGraph('name', $name);

        $description = trim((string) ($record['abstract'] ?? $record['description'] ?? ''));
        if ($description !== '') {
            $event->addToGraph('description', $description);
        }

        $startDate = (int) ($record['tx_maievents_start_date'] ?? 0);
        if ($startDate > 0) {
            $event->addToGraph('startDate', date('c', $startDate));
        }

        $endDate = (int) ($record['tx_maievents_end_date'] ?? 0);
        if ($endDate > 0) {
            $event->addToGraph('endDate', date('c', $endDate));
        }

        $location = trim((string) ($record['tx_maievents_location'] ?? ''));
        if ($location !== '') {
            $event->addToGraph('location', [
                '@type' => 'Place',
                'name' => $location,
            ]);
        }

        $event->addToGraph('eventStatus', 'https://schema.org/EventScheduled');

        return true;
    }

    private function collectFromLegacyStorage(StructuredDataCollectionEvent $event): void
    {
        $storagePids = $this->recordStorageResolver->resolveStoragePids($event->pageUid);
        if ($storagePids === []) {
            return;
        }

        $qb = $this->connectionPool->getQueryBuilderForTable('tx_maievents_event');
        $rows = $qb
            ->select('uid', 'title', 'description', 'location', 'start_date', 'end_date')
            ->from('tx_maievents_event')
            ->where(
                $qb->expr()->in(
                    'pid',
                    $qb->createNamedParameter($storagePids, Connection::PARAM_INT_ARRAY),
                ),
            )
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
}
