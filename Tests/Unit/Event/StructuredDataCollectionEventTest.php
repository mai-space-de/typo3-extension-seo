<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\Event;

use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class StructuredDataCollectionEventTest extends TestCase
{
    #[Test]
    public function constructorExposesPageUidAndRecord(): void
    {
        $record = ['uid' => 42, 'title' => 'My Page'];
        $event = new StructuredDataCollectionEvent(pageUid: 42, pageRecord: $record);

        self::assertSame(42, $event->pageUid);
        self::assertSame($record, $event->pageRecord);
    }

    #[Test]
    public function graphIsEmptyOnConstruction(): void
    {
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);

        self::assertSame([], $event->getGraph());
    }

    #[Test]
    public function addToGraphSetsAProperty(): void
    {
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'WebPage');

        self::assertSame(['@type' => 'WebPage'], $event->getGraph());
    }

    #[Test]
    public function addToGraphOverwritesPreviousValue(): void
    {
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('name', 'First');
        $event->addToGraph('name', 'Second');

        self::assertSame('Second', $event->getGraph()['name']);
    }

    #[Test]
    public function mergeIntoGraphCombinesData(): void
    {
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'WebPage');
        $event->mergeIntoGraph(['name' => 'Home', 'url' => 'https://example.com']);

        $graph = $event->getGraph();
        self::assertSame('WebPage', $graph['@type']);
        self::assertSame('Home', $graph['name']);
        self::assertSame('https://example.com', $graph['url']);
    }

    #[Test]
    public function setRootTypeSetsAtTypeKey(): void
    {
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->setRootType('Article');

        self::assertSame('Article', $event->getGraph()['@type']);
    }

    #[Test]
    public function setRootTypeOverwritesPreviousType(): void
    {
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->setRootType('WebPage');
        $event->setRootType('Article');

        self::assertSame('Article', $event->getGraph()['@type']);
    }

    #[Test]
    public function multiplePropertiesAccumulateInGraph(): void
    {
        $event = new StructuredDataCollectionEvent(pageUid: 99, pageRecord: ['title' => 'Test']);
        $event->addToGraph('@type', 'Organization');
        $event->addToGraph('name', 'ACME Corp');
        $event->addToGraph('url', 'https://acme.example.com');

        $graph = $event->getGraph();
        self::assertCount(3, $graph);
        self::assertSame('Organization', $graph['@type']);
        self::assertSame('ACME Corp', $graph['name']);
        self::assertSame('https://acme.example.com', $graph['url']);
    }
}
