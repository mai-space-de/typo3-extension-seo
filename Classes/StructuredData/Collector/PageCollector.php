<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\StructuredData\Collector;

use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;

final class PageCollector implements CollectorInterface
{
    public function collect(StructuredDataCollectionEvent $event): void
    {
        $record = $event->pageRecord;

        if (!empty($record['title'])) {
            $event->addToGraph('name', $record['title']);
        }

        if (!empty($record['description'])) {
            $event->addToGraph('description', $record['description']);
        }

        if (!empty($record['canonical_link'])) {
            $event->addToGraph('url', $record['canonical_link']);
        }

        if (!empty($record['crdate'])) {
            $event->addToGraph('datePublished', date('c', (int) $record['crdate']));
        }

        if (!empty($record['tstamp'])) {
            $event->addToGraph('dateModified', date('c', (int) $record['tstamp']));
        }

        if (empty($event->getGraph()['@type'])) {
            $schemaType = trim((string) ($record['tx_maiseo_schema_type'] ?? ''));
            $event->setRootType($schemaType !== '' ? $schemaType : 'WebPage');
        }
    }

    public function supportedTypes(): array
    {
        return ['*'];
    }

    public function priority(): int
    {
        return 100;
    }
}
