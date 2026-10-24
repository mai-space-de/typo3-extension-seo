<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\StructuredData\Collector;

use Maispace\MaiBase\Backend\CustomPageModule\DoktypeIds;
use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;

final class EventCollector implements CollectorInterface
{
    public function collect(StructuredDataCollectionEvent $event): void
    {
        if (!in_array($event->getGraph()['@type'] ?? '', $this->supportedTypes(), true)) {
            return;
        }

        $this->collectFromPageRecord($event);
    }

    public function supportedTypes(): array
    {
        return ['Event'];
    }

    public function priority(): int
    {
        return 70;
    }

    private function collectFromPageRecord(StructuredDataCollectionEvent $event): void
    {
        $record = $event->pageRecord;
        $doktype = (int) ($record['doktype'] ?? 0);
        $hasEventFields = isset($record['tx_maievents_start_date']) || isset($record['tx_maievents_location']);

        if ($doktype !== DoktypeIds::EVENT && !$hasEventFields) {
            return;
        }

        $name = trim((string) ($record['title'] ?? ''));
        if ($name === '') {
            return;
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
    }
}
