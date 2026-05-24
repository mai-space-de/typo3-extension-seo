<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\StructuredData\Collector;

use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;
use Maispace\MaiSeo\StructuredData\Collector\OrganizationCollector;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;

final class OrganizationCollectorTest extends TestCase
{
    private function makeSiteFinder(
        string $baseUrl = 'https://www.example.com/',
        string $siteTitle = 'Test Organization',
        ?string $logoPath = 'EXT:mai_theme/Resources/Public/Images/logo.svg',
    ): SiteFinder {
        $settings = \TYPO3\CMS\Core\Site\Entity\SiteSettings::createFromSettingsTree([
            'general.site.logo' => $logoPath,
            'general.site.favicon' => 'EXT:mai_theme/Resources/Public/Images/favicon.png',
            'pageUids.homepage' => 1,
        ]);

        $site = $this->createMock(Site::class);
        $site->method('getBase')->willReturn(new Uri($baseUrl));
        $site->method('getConfiguration')->willReturn(['websiteTitle' => $siteTitle]);
        $site->method('getSettings')->willReturn($settings);

        $finder = $this->createMock(SiteFinder::class);
        $finder->method('getSiteByPageId')->willReturn($site);

        return $finder;
    }

    #[Test]
    public function priorityIsEightyTest(): void
    {
        $collector = new OrganizationCollector($this->makeSiteFinder());

        self::assertSame(80, $collector->priority());
    }

    #[Test]
    public function supportedTypesContainsOrganizationTest(): void
    {
        $collector = new OrganizationCollector($this->makeSiteFinder());

        self::assertSame(['Organization'], $collector->supportedTypes());
    }

    #[Test]
    public function collectSkipsWhenTypeIsNotOrganizationTest(): void
    {
        $collector = new OrganizationCollector($this->makeSiteFinder());
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'WebPage');

        $collector->collect($event);

        self::assertArrayNotHasKey('name', $event->getGraph());
    }

    #[Test]
    public function collectAddsNameAndUrlFromSiteConfigTest(): void
    {
        $collector = new OrganizationCollector(
            $this->makeSiteFinder(
                baseUrl: 'https://www.bgm-pulheim.org/',
                siteTitle: 'Stadt Pulheim',
                logoPath: null,
            ),
        );
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'Organization');

        $collector->collect($event);

        $graph = $event->getGraph();
        self::assertSame('Stadt Pulheim', $graph['name']);
        self::assertSame('https://www.bgm-pulheim.org', $graph['url']);
    }

    #[Test]
    public function collectDoesNotOverrideNameWhenAlreadySetTest(): void
    {
        $collector = new OrganizationCollector($this->makeSiteFinder(logoPath: null));
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: ['title' => 'Home']);
        $event->addToGraph('@type', 'Organization');
        $event->addToGraph('name', 'Existing Name');

        $collector->collect($event);

        self::assertSame('Existing Name', $event->getGraph()['name']);
    }

    #[Test]
    public function collectDoesNotOverrideUrlWhenAlreadySetTest(): void
    {
        $collector = new OrganizationCollector($this->makeSiteFinder(logoPath: null));
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'Organization');
        $event->addToGraph('url', 'https://custom.example.com/override');

        $collector->collect($event);

        self::assertSame('https://custom.example.com/override', $event->getGraph()['url']);
    }

    #[Test]
    public function collectDoesNotAddLogoWhenLogoPathIsNullTest(): void
    {
        $collector = new OrganizationCollector(
            $this->makeSiteFinder(logoPath: null),
        );
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'Organization');

        $collector->collect($event);

        self::assertArrayNotHasKey('logo', $event->getGraph());
    }
}
