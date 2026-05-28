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
    private function makeConnectionPool(array $locationRows, array $openingHoursRows = []): ConnectionPool
    {
        $exprBuilder = $this->createMock(ExpressionBuilder::class);
        $exprBuilder->method('eq')->willReturn('1=1');

        $locationResult = $this->createMock(Result::class);
        $locationResult->method('fetchAllAssociative')->willReturn($locationRows);

        $locationQb = $this->createMock(QueryBuilder::class);
        $locationQb->method('select')->willReturnSelf();
        $locationQb->method('from')->willReturnSelf();
        $locationQb->method('where')->willReturnSelf();
        $locationQb->method('orderBy')->willReturnSelf();
        $locationQb->method('setMaxResults')->willReturnSelf();
        $locationQb->method('expr')->willReturn($exprBuilder);
        $locationQb->method('createNamedParameter')->willReturn(':p1');
        $locationQb->method('executeQuery')->willReturn($locationResult);

        $openingHoursResult = $this->createMock(Result::class);
        $openingHoursResult->method('fetchAllAssociative')->willReturn($openingHoursRows);

        $openingHoursQb = $this->createMock(QueryBuilder::class);
        $openingHoursQb->method('select')->willReturnSelf();
        $openingHoursQb->method('from')->willReturnSelf();
        $openingHoursQb->method('where')->willReturnSelf();
        $openingHoursQb->method('orderBy')->willReturnSelf();
        $openingHoursQb->method('expr')->willReturn($exprBuilder);
        $openingHoursQb->method('createNamedParameter')->willReturn(':p1');
        $openingHoursQb->method('executeQuery')->willReturn($openingHoursResult);

        $pool = $this->createMock(ConnectionPool::class);
        $pool->method('getQueryBuilderForTable')
            ->willReturnCallback(static function (string $table) use ($locationQb, $openingHoursQb): QueryBuilder {
                return $table === 'tx_mailocations_location' ? $locationQb : $openingHoursQb;
            });

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

    #[Test]
    public function collectAddsOpeningHoursSpecificationForRegularDaysTest(): void
    {
        $locationRows = [[
            'uid' => 5,
            'name' => 'Office',
            'street' => '',
            'zip' => '',
            'city' => '',
            'country' => '',
            'phone' => '',
            'email' => '',
            'latitude' => '0.0000000',
            'longitude' => '0.0000000',
            'description' => '',
        ]];
        $openingHoursRows = [
            [
                'day_of_week' => '0',
                'time_open' => '09:00',
                'time_close' => '17:00',
                'is_closed' => 0,
                'special_date' => null,
            ],
            [
                'day_of_week' => '4',
                'time_open' => '09:00',
                'time_close' => '13:00',
                'is_closed' => 0,
                'special_date' => null,
            ],
        ];
        $collector = new PlaceCollector($this->makeConnectionPool($locationRows, $openingHoursRows));
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'LocalBusiness');

        $collector->collect($event);

        $graph = $event->getGraph();
        self::assertArrayHasKey('openingHoursSpecification', $graph);
        self::assertCount(2, $graph['openingHoursSpecification']);

        $mondaySpec = $graph['openingHoursSpecification'][0];
        self::assertSame('OpeningHoursSpecification', $mondaySpec['@type']);
        self::assertSame('https://schema.org/Monday', $mondaySpec['dayOfWeek']);
        self::assertSame('09:00', $mondaySpec['opens']);
        self::assertSame('17:00', $mondaySpec['closes']);

        $fridaySpec = $graph['openingHoursSpecification'][1];
        self::assertSame('https://schema.org/Friday', $fridaySpec['dayOfWeek']);
        self::assertSame('09:00', $fridaySpec['opens']);
        self::assertSame('13:00', $fridaySpec['closes']);
    }

    #[Test]
    public function collectAddsSpecialDateOpeningHoursSpecificationTest(): void
    {
        $locationRows = [[
            'uid' => 3,
            'name' => 'Branch',
            'street' => '',
            'zip' => '',
            'city' => '',
            'country' => '',
            'phone' => '',
            'email' => '',
            'latitude' => '0.0000000',
            'longitude' => '0.0000000',
            'description' => '',
        ]];
        $openingHoursRows = [
            [
                'day_of_week' => '0',
                'time_open' => '10:00',
                'time_close' => '14:00',
                'is_closed' => 0,
                'special_date' => '2024-12-26',
            ],
        ];
        $collector = new PlaceCollector($this->makeConnectionPool($locationRows, $openingHoursRows));
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'LocalBusiness');

        $collector->collect($event);

        $graph = $event->getGraph();
        self::assertArrayHasKey('openingHoursSpecification', $graph);
        self::assertCount(1, $graph['openingHoursSpecification']);

        $spec = $graph['openingHoursSpecification'][0];
        self::assertSame('OpeningHoursSpecification', $spec['@type']);
        self::assertSame('2024-12-26', $spec['validFrom']);
        self::assertSame('2024-12-26', $spec['validThrough']);
        self::assertSame('10:00', $spec['opens']);
        self::assertSame('14:00', $spec['closes']);
        self::assertArrayNotHasKey('dayOfWeek', $spec);
    }

    #[Test]
    public function collectOmitsOpeningHoursSpecificationWhenNoneFoundTest(): void
    {
        $locationRows = [[
            'uid' => 2,
            'name' => 'No Hours Location',
            'street' => '',
            'zip' => '',
            'city' => '',
            'country' => '',
            'phone' => '',
            'email' => '',
            'latitude' => '0.0000000',
            'longitude' => '0.0000000',
            'description' => '',
        ]];
        $collector = new PlaceCollector($this->makeConnectionPool($locationRows, []));
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'Place');

        $collector->collect($event);

        self::assertArrayNotHasKey('openingHoursSpecification', $event->getGraph());
    }

    #[Test]
    public function collectMapsAllDaysOfWeekCorrectlyTest(): void
    {
        $locationRows = [[
            'uid' => 7,
            'name' => 'Full Week',
            'street' => '',
            'zip' => '',
            'city' => '',
            'country' => '',
            'phone' => '',
            'email' => '',
            'latitude' => '0.0000000',
            'longitude' => '0.0000000',
            'description' => '',
        ]];
        $openingHoursRows = array_map(
            static fn(string $day): array => [
                'day_of_week' => $day,
                'time_open' => '08:00',
                'time_close' => '18:00',
                'is_closed' => 0,
                'special_date' => null,
            ],
            ['0', '1', '2', '3', '4', '5', '6'],
        );
        $collector = new PlaceCollector($this->makeConnectionPool($locationRows, $openingHoursRows));
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'Place');

        $collector->collect($event);

        $specs = $event->getGraph()['openingHoursSpecification'];
        self::assertCount(7, $specs);

        $expectedDays = [
            'https://schema.org/Monday',
            'https://schema.org/Tuesday',
            'https://schema.org/Wednesday',
            'https://schema.org/Thursday',
            'https://schema.org/Friday',
            'https://schema.org/Saturday',
            'https://schema.org/Sunday',
        ];
        foreach ($specs as $index => $spec) {
            self::assertSame($expectedDays[$index], $spec['dayOfWeek']);
        }
    }

    #[Test]
    public function collectSkipsOpeningHoursRowWithUnknownDayOfWeekTest(): void
    {
        $locationRows = [[
            'uid' => 8,
            'name' => 'Office',
            'street' => '',
            'zip' => '',
            'city' => '',
            'country' => '',
            'phone' => '',
            'email' => '',
            'latitude' => '0.0000000',
            'longitude' => '0.0000000',
            'description' => '',
        ]];
        $openingHoursRows = [
            [
                'day_of_week' => '9',
                'time_open' => '09:00',
                'time_close' => '17:00',
                'is_closed' => 0,
                'special_date' => null,
            ],
        ];
        $collector = new PlaceCollector($this->makeConnectionPool($locationRows, $openingHoursRows));
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'Place');

        $collector->collect($event);

        self::assertArrayNotHasKey('openingHoursSpecification', $event->getGraph());
    }
}
