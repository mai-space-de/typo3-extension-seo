<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\StructuredData\Collector;

use Doctrine\DBAL\Result;
use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;
use Maispace\MaiSeo\StructuredData\Collector\EventCollector;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

final class EventCollectorTest extends CollectorTestCase
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
        $collector = new EventCollector($this->makeConnectionPool([]), $this->makeStorageResolver());

        self::assertSame(70, $collector->priority());
    }

    #[Test]
    public function supportedTypesContainsEventTest(): void
    {
        $collector = new EventCollector($this->makeConnectionPool([]), $this->makeStorageResolver());

        self::assertSame(['Event'], $collector->supportedTypes());
    }

    #[Test]
    public function collectSkipsWhenTypeIsNotEventTest(): void
    {
        $collector = new EventCollector($this->makeConnectionPool([]), $this->makeStorageResolver());
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'WebPage');

        $collector->collect($event);

        self::assertArrayNotHasKey('name', $event->getGraph());
        self::assertArrayNotHasKey('startDate', $event->getGraph());
    }

    #[Test]
    public function collectAddsEventPropertiesFromRecordTest(): void
    {
        $timestamp = 1700000000;
        $rows = [
            [
                'uid' => 1,
                'title' => 'Summer Concert',
                'description' => 'An evening of classical music.',
                'location' => 'City Hall',
                'start_date' => $timestamp,
                'end_date' => $timestamp + 7200,
            ],
        ];
        $collector = new EventCollector($this->makeConnectionPool($rows), $this->makeStorageResolver());
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'Event');

        $collector->collect($event);

        $graph = $event->getGraph();
        self::assertSame('Summer Concert', $graph['name']);
        self::assertSame('An evening of classical music.', $graph['description']);
        self::assertSame(date('c', $timestamp), $graph['startDate']);
        self::assertSame(date('c', $timestamp + 7200), $graph['endDate']);
        self::assertSame('https://schema.org/EventScheduled', $graph['eventStatus']);

        self::assertArrayHasKey('location', $graph);
        self::assertSame('Place', $graph['location']['@type']);
        self::assertSame('City Hall', $graph['location']['name']);
    }

    #[Test]
    public function collectHandlesEmptyOptionalFieldsTest(): void
    {
        $rows = [
            [
                'uid' => 1,
                'title' => 'Minimal Event',
                'description' => '',
                'location' => '',
                'start_date' => 0,
                'end_date' => 0,
            ],
        ];
        $collector = new EventCollector($this->makeConnectionPool($rows), $this->makeStorageResolver());
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'Event');

        $collector->collect($event);

        $graph = $event->getGraph();
        self::assertSame('Minimal Event', $graph['name']);
        self::assertSame('https://schema.org/EventScheduled', $graph['eventStatus']);
        self::assertArrayNotHasKey('description', $graph);
        self::assertArrayNotHasKey('startDate', $graph);
        self::assertArrayNotHasKey('endDate', $graph);
        self::assertArrayNotHasKey('location', $graph);
    }

    #[Test]
    public function collectSkipsWhenNoEventRecordFoundTest(): void
    {
        $collector = new EventCollector($this->makeConnectionPool([]), $this->makeStorageResolver());
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'Event');

        $collector->collect($event);

        self::assertArrayNotHasKey('name', $event->getGraph());
        self::assertArrayNotHasKey('startDate', $event->getGraph());
    }
}
