<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\StructuredData\Collector;

use Maispace\MaiBase\Backend\CustomPageModule\DoktypeIds;
use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;

final class PersonCollector implements CollectorInterface
{
    public function collect(StructuredDataCollectionEvent $event): void
    {
        if (!in_array($event->getGraph()['@type'] ?? '', $this->supportedTypes(), true)) {
            return;
        }

        $this->collectFromMemberPage($event);
    }

    public function supportedTypes(): array
    {
        return ['Person'];
    }

    public function priority(): int
    {
        return 70;
    }

    private function collectFromMemberPage(StructuredDataCollectionEvent $event): void
    {
        $record = $event->pageRecord;
        $doktype = (int) ($record['doktype'] ?? 0);
        $hasMemberFields = isset($record['tx_maimember_first_name']) || isset($record['tx_maimember_last_name']);

        if ($doktype !== DoktypeIds::MEMBER && !$hasMemberFields) {
            return;
        }

        $name = trim(
            trim((string) ($record['tx_maimember_first_name'] ?? ''))
            . ' '
            . trim((string) ($record['tx_maimember_last_name'] ?? '')),
        );
        if ($name === '') {
            $name = trim((string) ($record['title'] ?? ''));
        }
        if ($name === '') {
            return;
        }

        $event->addToGraph('name', $name);

        $jobTitle = trim((string) ($record['tx_maimember_position'] ?? ''));
        if ($jobTitle !== '') {
            $event->addToGraph('jobTitle', $jobTitle);
        }

        $email = trim((string) ($record['tx_maimember_email'] ?? ''));
        if ($email !== '') {
            $event->addToGraph('email', $email);
        }

        $phone = trim((string) ($record['tx_maimember_phone'] ?? ''));
        if ($phone !== '') {
            $event->addToGraph('telephone', $phone);
        }
    }
}
