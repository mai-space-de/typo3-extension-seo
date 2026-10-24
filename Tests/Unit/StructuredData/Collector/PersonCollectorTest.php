<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\StructuredData\Collector;

use Maispace\MaiBase\Backend\CustomPageModule\DoktypeIds;
use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;
use Maispace\MaiSeo\StructuredData\Collector\PersonCollector;
use PHPUnit\Framework\Attributes\Test;

final class PersonCollectorTest extends CollectorTestCase
{
    #[Test]
    public function priorityIsSeventyTest(): void
    {
        $collector = new PersonCollector();

        self::assertSame(70, $collector->priority());
    }

    #[Test]
    public function supportedTypesContainsPersonTest(): void
    {
        $collector = new PersonCollector();

        self::assertSame(['Person'], $collector->supportedTypes());
    }

    #[Test]
    public function collectSkipsWhenTypeIsNotPersonTest(): void
    {
        $collector = new PersonCollector();
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'WebPage');

        $collector->collect($event);

        self::assertArrayNotHasKey('name', $event->getGraph());
    }

    #[Test]
    public function collectAddsNameFromMemberDoktypePageTest(): void
    {
        $collector = new PersonCollector();
        $event = new StructuredDataCollectionEvent(pageUid: 88, pageRecord: [
            'doktype' => DoktypeIds::MEMBER,
            'tx_maimember_first_name' => 'Jane',
            'tx_maimember_last_name' => 'Doe',
            'tx_maimember_position' => 'Chair',
            'tx_maimember_email' => 'jane@example.com',
        ]);
        $event->addToGraph('@type', 'Person');

        $collector->collect($event);

        self::assertSame('Jane Doe', $event->getGraph()['name']);
        self::assertSame('Chair', $event->getGraph()['jobTitle']);
        self::assertSame('jane@example.com', $event->getGraph()['email']);
    }

    #[Test]
    public function collectSkipsWhenNoMemberFieldsAndWrongDoktypeTest(): void
    {
        $collector = new PersonCollector();
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: [
            'doktype' => 1,
            'title' => 'Plain page',
        ]);
        $event->addToGraph('@type', 'Person');

        $collector->collect($event);

        self::assertArrayNotHasKey('name', $event->getGraph());
    }

    #[Test]
    public function legacyTeamMemberTableIsNotQueriedTest(): void
    {
        $collector = new PersonCollector();
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'Person');

        $collector->collect($event);

        self::assertArrayNotHasKey('name', $event->getGraph());
    }
}
