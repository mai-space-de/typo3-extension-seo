<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\StructuredData\Collector;

use Doctrine\DBAL\Result;
use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;
use Maispace\MaiSeo\StructuredData\Collector\PersonCollector;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

final class PersonCollectorTest extends TestCase
{
    private function makeConnectionPool(array $rows): ConnectionPool
    {
        $exprBuilder = $this->createMock(ExpressionBuilder::class);
        $exprBuilder->method('eq')->willReturn('1=1');

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
        $collector = new PersonCollector($this->makeConnectionPool([]));

        self::assertSame(70, $collector->priority());
    }

    #[Test]
    public function supportedTypesContainsPersonTest(): void
    {
        $collector = new PersonCollector($this->makeConnectionPool([]));

        self::assertSame(['Person'], $collector->supportedTypes());
    }

    #[Test]
    public function collectSkipsWhenTypeIsNotPersonTest(): void
    {
        $collector = new PersonCollector($this->makeConnectionPool([]));
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'WebPage');

        $collector->collect($event);

        self::assertArrayNotHasKey('name', $event->getGraph());
    }

    #[Test]
    public function collectAddsNameAndJobTitleFromTeamMemberRecordTest(): void
    {
        $rows = [[
            'uid' => 1,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'role' => 'Developer',
            'bio' => 'Bio',
            'email' => 'john@example.com',
            'phone' => '+49123456',
        ]];
        $collector = new PersonCollector($this->makeConnectionPool($rows));
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'Person');

        $collector->collect($event);

        self::assertSame('John Doe', $event->getGraph()['name']);
        self::assertSame('Developer', $event->getGraph()['jobTitle']);
    }

    #[Test]
    public function collectSkipsWhenNoRecordsFoundTest(): void
    {
        $collector = new PersonCollector($this->makeConnectionPool([]));
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'Person');

        $collector->collect($event);

        self::assertArrayNotHasKey('name', $event->getGraph());
    }
}
