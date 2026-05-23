<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\StructuredData\Collector;

use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

final class NewsArticleCollector implements CollectorInterface
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function collect(StructuredDataCollectionEvent $event): void
    {
        if (!in_array($event->getGraph()['@type'] ?? '', $this->supportedTypes(), true)) {
            return;
        }

        $qb = $this->connectionPool->getQueryBuilderForTable('tx_mainews_news');
        $rows = $qb
            ->select('uid', 'title', 'teaser', 'date')
            ->from('tx_mainews_news')
            ->where($qb->expr()->eq('pid', $qb->createNamedParameter($event->pageUid, Connection::PARAM_INT)))
            ->orderBy('date', 'DESC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAllAssociative();

        if ($rows === []) {
            return;
        }

        $row = $rows[0];

        $event->addToGraph('headline', $row['title']);

        if (!empty($row['teaser'])) {
            $event->addToGraph('description', $row['teaser']);
        }

        if (!empty($row['date'])) {
            $event->addToGraph('datePublished', date('c', (int) $row['date']));
        }
    }

    public function supportedTypes(): array
    {
        return ['NewsArticle'];
    }

    public function priority(): int
    {
        return 70;
    }
}
