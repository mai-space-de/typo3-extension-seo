<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\StructuredData\Collector;

use Maispace\MaiBase\Backend\CustomPageModule\DoktypeIds;
use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;

final class JobPostingCollector implements CollectorInterface
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
        return ['JobPosting'];
    }

    public function priority(): int
    {
        return 70;
    }

    private function collectFromPageRecord(StructuredDataCollectionEvent $event): void
    {
        $record = $event->pageRecord;
        $doktype = (int) ($record['doktype'] ?? 0);
        $hasJobFields = isset($record['tx_maijobs_description']) || isset($record['tx_maijobs_status']);

        if ($doktype !== DoktypeIds::JOB && !$hasJobFields) {
            return;
        }

        $title = trim((string) ($record['title'] ?? ''));
        if ($title === '') {
            return;
        }

        $event->addToGraph('title', $title);

        $description = trim((string) ($record['tx_maijobs_description'] ?? ''));
        if ($description === '') {
            $description = trim((string) ($record['description'] ?? $record['abstract'] ?? ''));
        }
        if ($description !== '') {
            $event->addToGraph('description', $description);
        }

        $deadline = (int) ($record['tx_maijobs_deadline'] ?? 0);
        if ($deadline > 0) {
            $event->addToGraph('validThrough', date('c', $deadline));
        }

        $event->addToGraph('directApply', true);
    }
}
