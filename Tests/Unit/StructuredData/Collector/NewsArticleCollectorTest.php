<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\StructuredData\Collector;

use Doctrine\DBAL\Result;
use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;
use Maispace\MaiSeo\StructuredData\Collector\NewsArticleCollector;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

final class NewsArticleCollectorTest extends CollectorTestCase
{
    private function makeConnectionPool(array $rows): ConnectionPool
    {
        $exprBuilder = $this->createMock(ExpressionBuilder::class);
        $exprBuilder->method('eq')->willReturn('1=1');
        $exprBuilder->method('in')->willReturn('pid IN (1)');

        $result = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')->willReturn($rows);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
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
        $collector = new NewsArticleCollector($this->makeConnectionPool([]), $this->makeStorageResolver());

        self::assertSame(70, $collector->priority());
    }

    #[Test]
    public function supportedTypesContainsNewsArticleTest(): void
    {
        $collector = new NewsArticleCollector($this->makeConnectionPool([]), $this->makeStorageResolver());

        self::assertSame(['NewsArticle', 'Article'], $collector->supportedTypes());
    }

    #[Test]
    public function collectSkipsWhenTypeIsNotNewsArticleTest(): void
    {
        $collector = new NewsArticleCollector($this->makeConnectionPool([]), $this->makeStorageResolver());
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'WebPage');

        $collector->collect($event);

        self::assertArrayNotHasKey('headline', $event->getGraph());
    }

    #[Test]
    public function collectAddsHeadlineFromNewsRecordTest(): void
    {
        $rows = [['uid' => 1, 'title' => 'Test News', 'teaser' => 'Summary', 'date' => 1700000000]];
        $collector = new NewsArticleCollector($this->makeConnectionPool($rows), $this->makeStorageResolver());
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'NewsArticle');

        $collector->collect($event);

        self::assertSame('Test News', $event->getGraph()['headline']);
    }

    #[Test]
    public function collectSkipsWhenNoRecordsFoundTest(): void
    {
        $collector = new NewsArticleCollector($this->makeConnectionPool([]), $this->makeStorageResolver());
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'NewsArticle');

        $collector->collect($event);

        self::assertArrayNotHasKey('headline', $event->getGraph());
    }
}
