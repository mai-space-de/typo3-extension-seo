<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\StructuredData\Collector;

use Doctrine\DBAL\Result;
use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;
use Maispace\MaiSeo\StructuredData\Collector\PlaceCollector;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

final class PlaceCollectorTest extends TestCase
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
        $collector = new PlaceCollector($this->makeConnectionPool([]));

        self::assertSame(70, $collector->priority());
    }

    #[Test]
    public function supportedTypesContainsPlaceAndLocalBusinessTest(): void
    {
        $collector = new PlaceCollector($this->makeConnectionPool([]));

        self::assertSame(['Place', 'LocalBusiness'], $collector->supportedTypes());
    }

    #[Test]
    public function collectSkipsWhenTypeIsNotPlaceOrLocalBusinessTest(): void
    {
        $collector = new PlaceCollector($this->makeConnectionPool([]));
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'WebPage');

        $collector->collect($event);

        self::assertArrayNotHasKey('name', $event->getGraph());
    }

    #[Test]
    public function collectAddsNameTelephoneAndAddressFromLocationRecordTest(): void
    {
        $rows = [[
            'uid' => 1,
            'name' => 'Main Office',
            'street' => 'Main St 1',
            'zip' => '50170',
            'city' => 'Pulheim',
            'country' => 'DE',
            'phone' => '+49123',
            'email' => 'info@example.com',
            'latitude' => '50.9000000',
            'longitude' => '6.8000000',
            'description' => 'HQ',
        ]];
        $collector = new PlaceCollector($this->makeConnectionPool($rows));
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'Place');

        $collector->collect($event);

        self::assertSame('Main Office', $event->getGraph()['name']);
        self::assertSame('+49123', $event->getGraph()['telephone']);
        self::assertArrayHasKey('address', $event->getGraph());
        self::assertSame('PostalAddress', $event->getGraph()['address']['@type']);
        self::assertSame('Main St 1', $event->getGraph()['address']['streetAddress']);
    }

    #[Test]
    public function collectSkipsWhenNoRecordsFoundTest(): void
    {
        $collector = new PlaceCollector($this->makeConnectionPool([]));
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'Place');

        $collector->collect($event);

        self::assertArrayNotHasKey('name', $event->getGraph());
    }

    #[Test]
    public function geoIsAddedWhenCoordinatesAreNonZeroTest(): void
    {
        $rows = [[
            'uid' => 1,
            'name' => 'Main Office',
            'street' => '',
            'zip' => '',
            'city' => '',
            'country' => '',
            'phone' => '',
            'email' => '',
            'latitude' => '50.9000000',
            'longitude' => '6.8000000',
            'description' => '',
        ]];
        $collector = new PlaceCollector($this->makeConnectionPool($rows));
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'Place');

        $collector->collect($event);

        self::assertArrayHasKey('geo', $event->getGraph());
        self::assertSame('GeoCoordinates', $event->getGraph()['geo']['@type']);
        self::assertSame(50.9, $event->getGraph()['geo']['latitude']);
        self::assertSame(6.8, $event->getGraph()['geo']['longitude']);
    }
}
