<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\StructuredData\Collector;

use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;
use Maispace\MaiSeo\StructuredData\Collector\PageCollector;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Frontend\Page\PageInformation;

final class PageCollectorTest extends CollectorTestCase
{
    #[Test]
    public function priorityIsOneHundredTest(): void
    {
        $collector = new PageCollector();

        self::assertSame(100, $collector->priority());
    }

    #[Test]
    public function supportedTypesContainsWildcardTest(): void
    {
        $collector = new PageCollector();

        self::assertSame(['*'], $collector->supportedTypes());
    }

    #[Test]
    public function collectAddsTitleToGraphTest(): void
    {
        $collector = new PageCollector();
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: ['title' => 'Test Page']);

        $collector->collect($event);

        self::assertSame('Test Page', $event->getGraph()['name']);
    }

    #[Test]
    public function collectAddsDescriptionToGraphTest(): void
    {
        $collector = new PageCollector();
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: ['description' => 'Test description']);

        $collector->collect($event);

        self::assertSame('Test description', $event->getGraph()['description']);
    }

    #[Test]
    public function collectUsesCanonicalLinkWhenSetTest(): void
    {
        $collector = new PageCollector();
        $event = new StructuredDataCollectionEvent(
            pageUid: 1,
            pageRecord: ['canonical_link' => 'https://example.com/custom-url'],
        );

        $collector->collect($event);

        self::assertSame('https://example.com/custom-url', $event->getGraph()['url']);
    }

    #[Test]
    public function collectFallsBackToAbsolutePageUrlWhenCanonicalLinkIsEmptyTest(): void
    {
        $pageRecord = ['uid' => 42, 'title' => 'Test Page', 'slug' => '/test-page'];
        $pageArguments = new PageArguments(42, '0', []);
        $pageInformation = new PageInformation();
        $pageInformation->setId(42);
        $pageInformation->setPageRecord($pageRecord);

        $site = $this->createMock(Site::class);

        $language = $this->createMock(SiteLanguage::class);
        $language->method('getBase')->willReturn(new Uri('https://www.bgm-pulheim.org.ddev.site'));

        $request = new ServerRequest('https://www.bgm-pulheim.org.ddev.site/test-page');
        $request = $request->withAttribute('routing', $pageArguments);
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $request = $request->withAttribute('site', $site);
        $request = $request->withAttribute('language', $language);

        $GLOBALS['TYPO3_REQUEST'] = $request;

        try {
            $collector = new PageCollector();
            $event = new StructuredDataCollectionEvent(pageUid: 42, pageRecord: $pageRecord);

            $collector->collect($event);

            self::assertArrayHasKey('url', $event->getGraph());
            self::assertStringContainsString('www.bgm-pulheim.org.ddev.site', $event->getGraph()['url']);
            self::assertStringContainsString('/test-page', $event->getGraph()['url']);
        } finally {
            unset($GLOBALS['TYPO3_REQUEST']);
        }
    }

    #[Test]
    public function collectOmitsUrlWhenRequestIsNotAvailableTest(): void
    {
        if (isset($GLOBALS['TYPO3_REQUEST'])) {
            unset($GLOBALS['TYPO3_REQUEST']);
        }

        $collector = new PageCollector();
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: ['title' => 'Test Page']);

        $collector->collect($event);

        self::assertArrayNotHasKey('url', $event->getGraph());
    }

    #[Test]
    public function collectAddsDatePublishedWhenCrdateIsSetTest(): void
    {
        $collector = new PageCollector();
        $event = new StructuredDataCollectionEvent(
            pageUid: 1,
            pageRecord: ['crdate' => 1717200000],
        );

        $collector->collect($event);

        self::assertArrayHasKey('datePublished', $event->getGraph());
        self::assertIsString($event->getGraph()['datePublished']);
    }

    #[Test]
    public function collectAddsDateModifiedWhenTstampIsSetTest(): void
    {
        $collector = new PageCollector();
        $event = new StructuredDataCollectionEvent(
            pageUid: 1,
            pageRecord: ['tstamp' => 1717200000],
        );

        $collector->collect($event);

        self::assertArrayHasKey('dateModified', $event->getGraph());
        self::assertIsString($event->getGraph()['dateModified']);
    }

    #[Test]
    public function collectSetsWebPageAsDefaultTypeTest(): void
    {
        $collector = new PageCollector();
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: ['title' => 'Test Page']);

        $collector->collect($event);

        self::assertSame('WebPage', $event->getGraph()['@type']);
    }

    #[Test]
    public function collectUsesSchemaTypeFromRecordTest(): void
    {
        $collector = new PageCollector();
        $event = new StructuredDataCollectionEvent(
            pageUid: 1,
            pageRecord: ['tx_maiseo_schema_type' => 'Article'],
        );

        $collector->collect($event);

        self::assertSame('Article', $event->getGraph()['@type']);
    }

    #[Test]
    public function collectPreservesExistingTypeTest(): void
    {
        $collector = new PageCollector();
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: ['title' => 'Test Page']);
        $event->setRootType('LocalBusiness');

        $collector->collect($event);

        self::assertSame('LocalBusiness', $event->getGraph()['@type']);
    }
}
