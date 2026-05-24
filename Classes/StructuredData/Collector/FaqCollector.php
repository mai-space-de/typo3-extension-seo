<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\StructuredData\Collector;

use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

final class FaqCollector implements CollectorInterface
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function collect(StructuredDataCollectionEvent $event): void
    {
        if (!in_array($event->getGraph()['@type'] ?? '', $this->supportedTypes(), true)) {
            return;
        }

        $qb = $this->connectionPool->getQueryBuilderForTable('tx_maifaq_faq');
        $rows = $qb
            ->select('uid', 'question', 'answer')
            ->from('tx_maifaq_faq')
            ->where($qb->expr()->eq('pid', $qb->createNamedParameter($event->pageUid, Connection::PARAM_INT)))
            ->orderBy('sorting', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        if ($rows === []) {
            return;
        }

        $mainEntity = [];
        foreach ($rows as $row) {
            $faqItem = [
                '@type' => 'Question',
                'name' => $row['question'],
            ];

            if (!empty($row['answer'])) {
                $faqItem['acceptedAnswer'] = [
                    '@type' => 'Answer',
                    'text' => $row['answer'],
                ];
            }

            $mainEntity[] = $faqItem;
        }

        $event->addToGraph('mainEntity', $mainEntity);
    }

    public function supportedTypes(): array
    {
        return ['FAQPage'];
    }

    public function priority(): int
    {
        return 70;
    }
}
