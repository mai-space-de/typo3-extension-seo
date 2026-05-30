<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\StructuredData\Collector;

use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;
use TYPO3\CMS\Core\Site\SiteFinder;

final class WebSiteCollector implements CollectorInterface
{
    public function __construct(
        private readonly SiteFinder $siteFinder,
    ) {}

    public function collect(StructuredDataCollectionEvent $event): void
    {
        if (!in_array($event->getGraph()['@type'] ?? '', $this->supportedTypes(), true)) {
            return;
        }

        $site = $this->siteFinder->getSiteByPageId($event->pageUid);
        $baseUrl = rtrim((string) $site->getBase(), '/');
        $siteConfiguration = $site->getConfiguration();

        if (empty($event->getGraph()['name'])) {
            $siteTitle = $siteConfiguration['websiteTitle']
                ?? $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']
                ?? '';
            if ($siteTitle !== '') {
                $event->addToGraph('name', $siteTitle);
            }
        }

        if ($baseUrl !== '' && empty($event->getGraph()['url'])) {
            $event->addToGraph('url', $baseUrl);
        }
    }

    public function supportedTypes(): array
    {
        return ['WebSite'];
    }

    public function priority(): int
    {
        return 80;
    }
}
