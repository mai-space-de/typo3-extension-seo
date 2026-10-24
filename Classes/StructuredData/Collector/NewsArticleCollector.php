<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\StructuredData\Collector;

use Maispace\MaiBase\Backend\CustomPageModule\DoktypeIds;
use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;

final class NewsArticleCollector implements CollectorInterface
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
        return ['NewsArticle', 'Article'];
    }

    public function priority(): int
    {
        return 70;
    }

    private function collectFromPageRecord(StructuredDataCollectionEvent $event): void
    {
        $record = $event->pageRecord;
        $doktype = (int) ($record['doktype'] ?? 0);
        $hasNewsFields = isset($record['tx_mainews_teaser']) || isset($record['tx_mainews_date']);

        if ($doktype !== DoktypeIds::NEWS && !$hasNewsFields) {
            return;
        }

        $headline = trim((string) ($record['title'] ?? ''));
        if ($headline === '') {
            return;
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
    }
}
