<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\StructuredData\Collector;

use Maispace\MaiBase\Backend\CustomPageModule\DoktypeIds;
use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;
use Maispace\MaiSeo\StructuredData\Collector\EventCollector;
use PHPUnit\Framework\Attributes\Test;

final class EventCollectorTest extends CollectorTestCase
{
    #[Test]
    public function priorityIsSeventyTest(): void
    {
        $collector = new EventCollector();

        self::assertSame(70, $collector->priority());
    }

    #[Test]
    public function supportedTypesContainsEventTest(): void
    {
        $collector = new EventCollector();

        self::assertSame(['Event'], $collector->supportedTypes());
    }

    #[Test]
    public function collectSkipsWhenTypeIsNotEventTest(): void
    {
        $collector = new EventCollector();
        $event = new StructuredDataCollectionEvent(
            pageUid: 1,
            pageRecord: [
                'doktype' => DoktypeIds::EVENT,
                'title' => 'Ignored',
            ],
        );
        $event->addToGraph('@type', 'WebPage');

        $collector->collect($event);

        self::assertArrayNotHasKey('name', $event->getGraph());
        self::assertArrayNotHasKey('startDate', $event->getGraph());
    }

    #[Test]
    public function collectAddsFieldsFromEventDoktypePageTest(): void
    {
        $timestamp = 1700000000;
        $collector = new EventCollector();
        $event = new StructuredDataCollectionEvent(
            pageUid: 55,
            pageRecord: [
                'doktype' => DoktypeIds::EVENT,
                'title' => 'Page Event',
                'abstract' => 'From page abstract',
                'tx_maievents_location' => 'Hall A',
                'tx_maievents_start_date' => $timestamp,
                'tx_maievents_end_date' => $timestamp + 3600,
            ],
        );
        $event->addToGraph('@type', 'Event');

        $collector->collect($event);

        $graph = $event->getGraph();
        self::assertSame('Page Event', $graph['name']);
        self::assertSame('From page abstract', $graph['description']);
        self::assertSame(date('c', $timestamp), $graph['startDate']);
        self::assertSame(date('c', $timestamp + 3600), $graph['endDate']);
        self::assertSame('Hall A', $graph['location']['name']);
        self::assertSame('Place', $graph['location']['@type']);
        self::assertSame('https://schema.org/EventScheduled', $graph['eventStatus']);
    }

    #[Test]
    public function collectHandlesEmptyOptionalFieldsOnEventPageTest(): void
    {
        $collector = new EventCollector();
        $event = new StructuredDataCollectionEvent(
            pageUid: 55,
            pageRecord: [
                'doktype' => DoktypeIds::EVENT,
                'title' => 'Minimal Event',
            ],
        );
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
    public function collectSkipsWhenPageIsNotEventDoktypeTest(): void
    {
        $collector = new EventCollector();
        $event = new StructuredDataCollectionEvent(
            pageUid: 1,
            pageRecord: [
                'doktype' => 1,
                'title' => 'Regular page',
            ],
        );
        $event->addToGraph('@type', 'Event');

        $collector->collect($event);

        self::assertArrayNotHasKey('name', $event->getGraph());
        self::assertArrayNotHasKey('startDate', $event->getGraph());
    }

    #[Test]
    public function collectSkipsWhenEventPageHasEmptyTitleTest(): void
    {
        $collector = new EventCollector();
        $event = new StructuredDataCollectionEvent(
            pageUid: 1,
            pageRecord: [
                'doktype' => DoktypeIds::EVENT,
                'title' => '',
            ],
        );
        $event->addToGraph('@type', 'Event');

        $collector->collect($event);

        self::assertArrayNotHasKey('name', $event->getGraph());
    }
}
