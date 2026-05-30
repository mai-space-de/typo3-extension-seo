<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\StructuredData\Collector;

use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;
use Maispace\MaiSeo\StructuredData\Collector\FaqCollector;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

final class FaqCollectorTest extends CollectorTestCase
{
    private function makeConnectionPool(array $rows): ConnectionPool
    {
        $exprBuilder = $this->createMock(ExpressionBuilder::class);
        $exprBuilder->method('eq')->willReturn('1=1');
        $exprBuilder->method('in')->willReturn('pid IN (1)');

        $result = $this->createMock(\Doctrine\DBAL\Result::class);
        $result->method('fetchAllAssociative')->willReturn($rows);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('expr')->willReturn($exprBuilder);
        $qb->method('createNamedParameter')->willReturn(':p1');
        $qb->method('executeQuery')->willReturn($result);

        $pool = $this->createMock(ConnectionPool::class);
        $pool->method('getQueryBuilderForTable')->willReturn($qb);

        return $pool;
    }

    #[Test]
    public function priorityIsSeventyTest(): void
    {
        $collector = new FaqCollector($this->makeConnectionPool([]), $this->makeStorageResolver());

        self::assertSame(70, $collector->priority());
    }

    #[Test]
    public function supportedTypesContainsFAQPageTest(): void
    {
        $collector = new FaqCollector($this->makeConnectionPool([]), $this->makeStorageResolver());

        self::assertSame(['FAQPage'], $collector->supportedTypes());
    }

    #[Test]
    public function collectSkipsWhenTypeIsNotFAQPageTest(): void
    {
        $collector = new FaqCollector($this->makeConnectionPool([]), $this->makeStorageResolver());
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'WebPage');

        $collector->collect($event);

        self::assertArrayNotHasKey('mainEntity', $event->getGraph());
    }

    #[Test]
    public function collectAddsMainEntityFromFaqRecordsTest(): void
    {
        $rows = [
            [
                'uid' => 1,
                'question' => 'What are your opening hours?',
                'answer' => '<p>We are open Mon-Fri 9am-5pm.</p>',
            ],
            [
                'uid' => 2,
                'question' => 'How do I register?',
                'answer' => '<p>You can register online at our website.</p>',
            ],
        ];
        $collector = new FaqCollector($this->makeConnectionPool($rows), $this->makeStorageResolver());
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'FAQPage');

        $collector->collect($event);

        $graph = $event->getGraph();
        self::assertArrayHasKey('mainEntity', $graph);
        self::assertCount(2, $graph['mainEntity']);

        self::assertSame('Question', $graph['mainEntity'][0]['@type']);
        self::assertSame('What are your opening hours?', $graph['mainEntity'][0]['name']);
        self::assertSame('Answer', $graph['mainEntity'][0]['acceptedAnswer']['@type']);
        self::assertSame('<p>We are open Mon-Fri 9am-5pm.</p>', $graph['mainEntity'][0]['acceptedAnswer']['text']);

        self::assertSame('How do I register?', $graph['mainEntity'][1]['name']);
    }

    #[Test]
    public function collectHandlesFaqItemWithEmptyAnswerTest(): void
    {
        $rows = [
            [
                'uid' => 1,
                'question' => 'What is the purpose?',
                'answer' => '',
            ],
        ];
        $collector = new FaqCollector($this->makeConnectionPool($rows), $this->makeStorageResolver());
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'FAQPage');

        $collector->collect($event);

        $graph = $event->getGraph();
        self::assertArrayHasKey('mainEntity', $graph);
        self::assertCount(1, $graph['mainEntity']);
        self::assertSame('What is the purpose?', $graph['mainEntity'][0]['name']);
        self::assertArrayNotHasKey('acceptedAnswer', $graph['mainEntity'][0]);
    }

    #[Test]
    public function collectSkipsWhenNoFaqRecordsFoundTest(): void
    {
        $collector = new FaqCollector($this->makeConnectionPool([]), $this->makeStorageResolver());
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'FAQPage');

        $collector->collect($event);

        self::assertArrayNotHasKey('mainEntity', $event->getGraph());
    }
}
