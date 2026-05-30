<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\StructuredData\Collector;

use Doctrine\DBAL\Result;
use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;
use Maispace\MaiSeo\StructuredData\Collector\JobPostingCollector;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

final class JobPostingCollectorTest extends CollectorTestCase
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
        $collector = new JobPostingCollector($this->makeConnectionPool([]), $this->makeStorageResolver());

        self::assertSame(70, $collector->priority());
    }

    #[Test]
    public function supportedTypesContainsJobPostingTest(): void
    {
        $collector = new JobPostingCollector($this->makeConnectionPool([]), $this->makeStorageResolver());

        self::assertSame(['JobPosting'], $collector->supportedTypes());
    }

    #[Test]
    public function collectSkipsWhenTypeIsNotJobPostingTest(): void
    {
        $collector = new JobPostingCollector($this->makeConnectionPool([]), $this->makeStorageResolver());
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'WebPage');

        $collector->collect($event);

        self::assertArrayNotHasKey('title', $event->getGraph());
    }

    #[Test]
    public function collectAddsTitleFromJobRecordTest(): void
    {
        $rows = [['uid' => 1, 'title' => 'PHP Dev', 'description' => 'Great job', 'deadline' => 1750000000, 'status' => 'open']];
        $collector = new JobPostingCollector($this->makeConnectionPool($rows), $this->makeStorageResolver());
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'JobPosting');

        $collector->collect($event);

        self::assertSame('PHP Dev', $event->getGraph()['title']);
        self::assertSame('Great job', $event->getGraph()['description']);
        self::assertTrue($event->getGraph()['directApply']);
        self::assertArrayHasKey('validThrough', $event->getGraph());
    }

    #[Test]
    public function collectSkipsWhenNoRecordsFoundTest(): void
    {
        $collector = new JobPostingCollector($this->makeConnectionPool([]), $this->makeStorageResolver());
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'JobPosting');

        $collector->collect($event);

        self::assertArrayNotHasKey('title', $event->getGraph());
    }
}
