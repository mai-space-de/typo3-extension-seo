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
        array $extraSettings = [],
    ): SiteFinder {
        $settings = \TYPO3\CMS\Core\Site\Entity\SiteSettings::createFromSettingsTree([
            'general.site.logo' => $logoPath,
            'general.site.favicon' => 'EXT:mai_theme/Resources/Public/Images/favicon.png',
            'pageUids.homepage' => 1,
        ] + $extraSettings);

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

    #[Test]
    public function collectAddsAddressAndContactFromSiteSettingsTest(): void
    {
        $collector = new OrganizationCollector($this->makeSiteFinder(logoPath: null, extraSettings: [
            'seo.organization.legalName' => 'Verein e.V.',
            'seo.organization.streetAddress' => 'Hauptstr. 1',
            'seo.organization.postalCode' => '50259',
            'seo.organization.locality' => 'Pulheim',
            'seo.organization.country' => 'DE',
            'seo.organization.telephone' => '+49 175 2589970',
            'seo.organization.email' => 'info@example.org',
        ]));
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'Organization');

        $collector->collect($event);

        $graph = $event->getGraph();
        self::assertSame('Verein e.V.', $graph['legalName']);
        self::assertSame([
            '@type' => 'PostalAddress',
            'streetAddress' => 'Hauptstr. 1',
            'postalCode' => '50259',
            'addressLocality' => 'Pulheim',
            'addressCountry' => 'DE',
        ], $graph['address']);
        self::assertSame('+49 175 2589970', $graph['telephone']);
        self::assertSame('info@example.org', $graph['email']);
        self::assertSame('ContactPoint', $graph['contactPoint']['@type']);
        self::assertSame('info@example.org', $graph['contactPoint']['email']);
    }

    #[Test]
    public function collectOmitsOrganizationDetailsWhenSettingsAreEmptyTest(): void
    {
        $collector = new OrganizationCollector($this->makeSiteFinder(logoPath: null));
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'Organization');

        $collector->collect($event);

        $graph = $event->getGraph();
        foreach (['legalName', 'address', 'telephone', 'email', 'contactPoint', 'sameAs'] as $key) {
            self::assertArrayNotHasKey($key, $graph);
        }
    }

    #[Test]
    public function collectKeepsOnlyHttpsUrlsInSameAsTest(): void
    {
        $collector = new OrganizationCollector($this->makeSiteFinder(logoPath: null, extraSettings: [
            'seo.organization.sameAs' => 'https://www.wikidata.org/wiki/Q1, javascript:alert(1), http://insecure.example.org, https://www.youtube.com/@verein',
        ]));
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'Organization');

        $collector->collect($event);

        self::assertSame(
            ['https://www.wikidata.org/wiki/Q1', 'https://www.youtube.com/@verein'],
            $event->getGraph()['sameAs'],
        );
    }
}
