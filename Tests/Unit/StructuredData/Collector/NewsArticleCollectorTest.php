<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\StructuredData\Collector;

use Maispace\MaiBase\Backend\CustomPageModule\DoktypeIds;
use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;
use Maispace\MaiSeo\StructuredData\Collector\NewsArticleCollector;
use PHPUnit\Framework\Attributes\Test;

final class NewsArticleCollectorTest extends CollectorTestCase
{
    #[Test]
    public function priorityIsSeventyTest(): void
    {
        $collector = new NewsArticleCollector();

        self::assertSame(70, $collector->priority());
    }

    #[Test]
    public function supportedTypesContainsNewsArticleTest(): void
    {
        $collector = new NewsArticleCollector();

        self::assertSame(['NewsArticle', 'Article'], $collector->supportedTypes());
    }

    #[Test]
    public function collectSkipsWhenTypeIsNotNewsArticleTest(): void
    {
        $collector = new NewsArticleCollector();
        $event = new StructuredDataCollectionEvent(
            pageUid: 1,
            pageRecord: [
                'doktype' => DoktypeIds::NEWS,
                'title' => 'Ignored',
            ],
        );
        $event->addToGraph('@type', 'WebPage');

        $collector->collect($event);

        self::assertArrayNotHasKey('headline', $event->getGraph());
    }

    #[Test]
    public function collectAddsFieldsFromNewsDoktypePageTest(): void
    {
        $collector = new NewsArticleCollector();
        $event = new StructuredDataCollectionEvent(
            pageUid: 42,
            pageRecord: [
                'doktype' => DoktypeIds::NEWS,
                'title' => 'Page News',
                'tx_mainews_teaser' => 'Teaser text',
                'tx_mainews_date' => 1700000000,
            ],
        );
        $event->addToGraph('@type', 'NewsArticle');

        $collector->collect($event);

        $graph = $event->getGraph();
        self::assertSame('Page News', $graph['headline']);
        self::assertSame('Teaser text', $graph['description']);
        self::assertSame(date('c', 1700000000), $graph['datePublished']);
    }

    #[Test]
    public function collectUsesDescriptionWhenTeaserMissingTest(): void
    {
        $collector = new NewsArticleCollector();
        $event = new StructuredDataCollectionEvent(
            pageUid: 42,
            pageRecord: [
                'doktype' => DoktypeIds::NEWS,
                'title' => 'Page News',
                'description' => 'Fallback description',
            ],
        );
        $event->addToGraph('@type', 'NewsArticle');

        $collector->collect($event);

        self::assertSame('Fallback description', $event->getGraph()['description']);
    }

    #[Test]
    public function collectSkipsWhenPageIsNotNewsDoktypeTest(): void
    {
        $collector = new NewsArticleCollector();
        $event = new StructuredDataCollectionEvent(
            pageUid: 1,
            pageRecord: [
                'doktype' => 1,
                'title' => 'Regular page',
            ],
        );
        $event->addToGraph('@type', 'NewsArticle');

        $collector->collect($event);

        self::assertArrayNotHasKey('headline', $event->getGraph());
    }

    #[Test]
    public function collectSkipsWhenNewsPageHasEmptyTitleTest(): void
    {
        $collector = new NewsArticleCollector();
        $event = new StructuredDataCollectionEvent(
            pageUid: 1,
            pageRecord: [
                'doktype' => DoktypeIds::NEWS,
                'title' => '   ',
            ],
        );
        $event->addToGraph('@type', 'NewsArticle');

        $collector->collect($event);

        self::assertArrayNotHasKey('headline', $event->getGraph());
    }
}
