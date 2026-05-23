<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\StructuredData\Collector;

use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

final class PersonCollector implements CollectorInterface
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function collect(StructuredDataCollectionEvent $event): void
    {
        if (!in_array($event->getGraph()['@type'] ?? '', $this->supportedTypes(), true)) {
            return;
        }

        $qb = $this->connectionPool->getQueryBuilderForTable('tx_maiteam_team_member');
        $rows = $qb
            ->select('uid', 'first_name', 'last_name', 'role', 'bio', 'email', 'phone')
            ->from('tx_maiteam_team_member')
            ->where($qb->expr()->eq('pid', $qb->createNamedParameter($event->pageUid, Connection::PARAM_INT)))
            ->orderBy('sorting', 'ASC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAllAssociative();

        if ($rows === []) {
            return;
        }

        $row = $rows[0];

        $name = trim($row['first_name'] . ' ' . $row['last_name']);
        if ($name !== '') {
            $event->addToGraph('name', $name);
        }

        if (!empty($row['role'])) {
            $event->addToGraph('jobTitle', $row['role']);
        }

        if (!empty($row['bio'])) {
            $event->addToGraph('description', $row['bio']);
        }

        if (!empty($row['email'])) {
            $event->addToGraph('email', $row['email']);
        }

        if (!empty($row['phone'])) {
            $event->addToGraph('telephone', $row['phone']);
        }
    }

    public function supportedTypes(): array
    {
        return ['Person'];
    }

    public function priority(): int
    {
        return 70;
    }
}
