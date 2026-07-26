<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\StructuredData\Collector;

use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;
use Maispace\MaiSeo\StructuredData\Collector\BreadcrumbCollector;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Routing\PageRouter;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;

final class BreadcrumbCollectorTest extends TestCase
{
    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    private function makeSiteFinder(
        string $baseUrl = 'https://www.example.com/',
        array $urlMap = [],
    ): SiteFinder {
        $site = $this->createMock(Site::class);
        $site->method('getBase')->willReturn(new Uri($baseUrl));

        $router = $this->createMock(PageRouter::class);
        $router->method('generateUri')->willReturnCallback(
            fn(int $pageUid) => new Uri($urlMap[$pageUid] ?? '/'),
        );
        $site->method('getRouter')->willReturn($router);

        $finder = $this->createMock(SiteFinder::class);
        $finder->method('getSiteByPageId')->willReturn($site);

        return $finder;
    }

    private function mockRootline(array $pages): void
    {
        $rootlineMock = $this->createMock(RootlineUtility::class);
        $rootlineMock->method('get')->willReturn($pages);
        GeneralUtility::addInstance(RootlineUtility::class, $rootlineMock);
    }

    // --- Interface methods ---

    #[Test]
    public function priorityIsNinety(): void
    {
        $collector = new BreadcrumbCollector($this->makeSiteFinder());
        self::assertSame(90, $collector->priority());
    }

    #[Test]
    public function supportedTypesContainsAll(): void
    {
        $collector = new BreadcrumbCollector($this->makeSiteFinder());
        self::assertSame(['*'], $collector->supportedTypes());
    }

    // --- Edge cases ---

    #[Test]
    public function collectReturnsEarlyWhenRootlineUtilityThrows(): void
    {
        $rootlineMock = $this->createMock(RootlineUtility::class);
        $rootlineMock->method('get')->willThrowException(new \RuntimeException());
        GeneralUtility::addInstance(RootlineUtility::class, $rootlineMock);

        $collector = new BreadcrumbCollector($this->makeSiteFinder());
        $event = new StructuredDataCollectionEvent(pageUid: 42, pageRecord: []);

        $collector->collect($event);

        self::assertSame([], $event->getGraph());
    }

    #[Test]
    public function collectReturnsEarlyWhenRootlineHasOnlyOnePage(): void
    {
        $this->mockRootline([
            ['uid' => 1, 'title' => 'Home', 'doktype' => 1],
        ]);

        $collector = new BreadcrumbCollector($this->makeSiteFinder());
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);

        $collector->collect($event);

        self::assertSame([], $event->getGraph());
    }

    #[Test]
    public function collectReturnsEarlyWhenFilteredRootlineHasOnlyOnePage(): void
    {
        $this->mockRootline([
            ['uid' => 1, 'title' => 'Home', 'doktype' => 254],
            ['uid' => 2, 'title' => 'Actual Page', 'doktype' => 1],
        ]);

        $collector = new BreadcrumbCollector($this->makeSiteFinder());
        $event = new StructuredDataCollectionEvent(pageUid: 2, pageRecord: []);

        $collector->collect($event);

        self::assertSame([], $event->getGraph());
    }

    // --- Filtering behavior ---

    #[Test]
    public function collectFiltersOutSysFolderPages(): void
    {
        $this->mockRootline([
            ['uid' => 1, 'title' => 'Home', 'doktype' => 1],
            ['uid' => 2, 'title' => 'SysFolder', 'doktype' => 254],
            ['uid' => 3, 'title' => 'About', 'doktype' => 1],
        ]);

        $collector = new BreadcrumbCollector(
            $this->makeSiteFinder(urlMap: [1 => '/', 3 => '/about']),
        );
        $event = new StructuredDataCollectionEvent(pageUid: 3, pageRecord: []);

        $collector->collect($event);

        $items = $event->getGraph()['breadcrumb']['itemListElement'];
        self::assertCount(2, $items);
        // array_reverse puts current page first, root last
        self::assertSame('About', $items[0]['name']);
        self::assertSame('Home', $items[1]['name']);
        // Position reflects display order
        self::assertSame(1, $items[0]['position']);
        self::assertSame(2, $items[1]['position']);
    }

    #[Test]
    public function collectFiltersOutSpacerPages(): void
    {
        $this->mockRootline([
            ['uid' => 1, 'title' => 'Home', 'doktype' => 1],
            ['uid' => 2, 'title' => 'Hauptnavigation', 'doktype' => 199],
            ['uid' => 3, 'title' => 'About', 'doktype' => 1],
        ]);

        $collector = new BreadcrumbCollector(
            $this->makeSiteFinder(urlMap: [1 => '/', 3 => '/about']),
        );
        $event = new StructuredDataCollectionEvent(pageUid: 3, pageRecord: []);

        $collector->collect($event);

        $items = $event->getGraph()['breadcrumb']['itemListElement'];
        self::assertCount(2, $items);
        self::assertSame('About', $items[0]['name']);
        self::assertSame('Home', $items[1]['name']);
    }

    #[Test]
    public function collectFiltersOutPagesWithEmptyTitles(): void
    {
        $this->mockRootline([
            ['uid' => 1, 'title' => 'Home', 'doktype' => 1],
            ['uid' => 2, 'title' => '', 'doktype' => 1],
            ['uid' => 3, 'title' => 'Contact', 'doktype' => 1],
        ]);

        $collector = new BreadcrumbCollector(
            $this->makeSiteFinder(urlMap: [1 => '/', 3 => '/contact']),
        );
        $event = new StructuredDataCollectionEvent(pageUid: 3, pageRecord: []);

        $collector->collect($event);

        $items = $event->getGraph()['breadcrumb']['itemListElement'];
        self::assertCount(2, $items);
        // array_reverse puts current page first, root last
        self::assertSame('Contact', $items[0]['name']);
        self::assertSame('Home', $items[1]['name']);
    }

    // --- URL generation ---

    #[Test]
    public function collectGeneratesSlugBasedUrls(): void
    {
        $this->mockRootline([
            ['uid' => 1, 'title' => 'Home', 'doktype' => 1],
            ['uid' => 10, 'title' => 'Services', 'doktype' => 1],
            ['uid' => 42, 'title' => 'Consulting', 'doktype' => 1],
        ]);

        $collector = new BreadcrumbCollector(
            $this->makeSiteFinder(
                baseUrl: 'https://example.com/',
                urlMap: [
                    1 => '/',
                    10 => '/services',
                    42 => '/services/consulting',
                ],
            ),
        );
        $event = new StructuredDataCollectionEvent(pageUid: 42, pageRecord: []);

        $collector->collect($event);

        $items = $event->getGraph()['breadcrumb']['itemListElement'];
        self::assertCount(3, $items);
        // array_reverse puts current page first, root last
        self::assertSame('https://example.com/services/consulting', $items[0]['item']);
        self::assertSame('https://example.com/services', $items[1]['item']);
        self::assertSame('https://example.com/', $items[2]['item']);
    }

    #[Test]
    public function collectMakesRelativeUrlAbsolute(): void
    {
        $this->mockRootline([
            ['uid' => 1, 'title' => 'Home', 'doktype' => 1],
            ['uid' => 2, 'title' => 'About', 'doktype' => 1],
        ]);

        $site = $this->createMock(Site::class);
        $site->method('getBase')->willReturn(new Uri('https://example.com/'));

        $router = $this->createMock(PageRouter::class);
        $router->method('generateUri')->willReturnCallback(
            fn(int $pageUid) => new Uri('/slug-for-' . $pageUid),
        );
        $site->method('getRouter')->willReturn($router);

        $finder = $this->createMock(SiteFinder::class);
        $finder->method('getSiteByPageId')->willReturn($site);

        $collector = new BreadcrumbCollector($finder);
        $event = new StructuredDataCollectionEvent(pageUid: 2, pageRecord: []);

        $collector->collect($event);

        $items = $event->getGraph()['breadcrumb']['itemListElement'];
        // array_reverse puts current page first, root last
        self::assertSame('https://example.com/slug-for-2', $items[0]['item']);
        self::assertSame('https://example.com/slug-for-1', $items[1]['item']);
    }

    #[Test]
    public function collectKeepsAlreadyAbsoluteUrl(): void
    {
        $this->mockRootline([
            ['uid' => 1, 'title' => 'Home', 'doktype' => 1],
            ['uid' => 2, 'title' => 'About', 'doktype' => 1],
        ]);

        $site = $this->createMock(Site::class);
        $site->method('getBase')->willReturn(new Uri('https://example.com/'));

        $router = $this->createMock(PageRouter::class);
        $router->method('generateUri')->willReturnCallback(
            fn(int $pageUid) => new Uri('https://example.com/path/' . $pageUid),
        );
        $site->method('getRouter')->willReturn($router);

        $finder = $this->createMock(SiteFinder::class);
        $finder->method('getSiteByPageId')->willReturn($site);

        $collector = new BreadcrumbCollector($finder);
        $event = new StructuredDataCollectionEvent(pageUid: 2, pageRecord: []);

        $collector->collect($event);

        $items = $event->getGraph()['breadcrumb']['itemListElement'];
        // array_reverse puts current page first, root last
        self::assertSame('https://example.com/path/2', $items[0]['item']);
        self::assertSame('https://example.com/path/1', $items[1]['item']);
    }

    // --- ListItem structure ---

    #[Test]
    public function collectSetsCorrectPositionOrder(): void
    {
        $this->mockRootline([
            ['uid' => 1, 'title' => 'Root', 'doktype' => 1],
            ['uid' => 2, 'title' => 'Section', 'doktype' => 1],
            ['uid' => 3, 'title' => 'Detail', 'doktype' => 1],
        ]);

        $collector = new BreadcrumbCollector(
            $this->makeSiteFinder(urlMap: [
                1 => '/',
                2 => '/section',
                3 => '/section/detail',
            ]),
        );
        $event = new StructuredDataCollectionEvent(pageUid: 3, pageRecord: []);

        $collector->collect($event);

        $items = $event->getGraph()['breadcrumb']['itemListElement'];
        // array_reverse puts current page first, root last
        self::assertSame('Detail', $items[0]['name']);
        self::assertSame('Section', $items[1]['name']);
        self::assertSame('Root', $items[2]['name']);
        // Position 1 = current (Detail), position 2 = parent (Section), position 3 = root
        self::assertSame(1, $items[0]['position']);
        self::assertSame(2, $items[1]['position']);
        self::assertSame(3, $items[2]['position']);
    }

    #[Test]
    public function collectSetsListItemTypeOnEachElement(): void
    {
        $this->mockRootline([
            ['uid' => 1, 'title' => 'Root', 'doktype' => 1],
            ['uid' => 2, 'title' => 'Leaf', 'doktype' => 1],
        ]);

        $collector = new BreadcrumbCollector(
            $this->makeSiteFinder(urlMap: [1 => '/', 2 => '/leaf']),
        );
        $event = new StructuredDataCollectionEvent(pageUid: 2, pageRecord: []);

        $collector->collect($event);

        $items = $event->getGraph()['breadcrumb']['itemListElement'];
        self::assertCount(2, $items);
        foreach ($items as $item) {
            self::assertSame('ListItem', $item['@type']);
        }
    }

    #[Test]
    public function collectSetsBreadcrumbListType(): void
    {
        $this->mockRootline([
            ['uid' => 1, 'title' => 'Home', 'doktype' => 1],
            ['uid' => 2, 'title' => 'Page', 'doktype' => 1],
        ]);

        $collector = new BreadcrumbCollector(
            $this->makeSiteFinder(urlMap: [1 => '/', 2 => '/page']),
        );
        $event = new StructuredDataCollectionEvent(pageUid: 2, pageRecord: []);

        $collector->collect($event);

        $graph = $event->getGraph();
        self::assertArrayHasKey('breadcrumb', $graph);
        self::assertSame('BreadcrumbList', $graph['breadcrumb']['@type']);
    }
}
