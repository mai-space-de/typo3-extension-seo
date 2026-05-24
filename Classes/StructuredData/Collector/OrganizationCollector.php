<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\StructuredData\Collector;

use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

final class OrganizationCollector implements CollectorInterface
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

        // Name — use the site title unless PageCollector already set it
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

        // Logo — resolve EXT: path to absolute URL
        $logoPath = $site->getSettings()->get('general.site.logo');
        if (is_string($logoPath) && $logoPath !== '') {
            $logoUrl = $this->resolveLogoUrl($logoPath, $baseUrl);
            if ($logoUrl !== null) {
                $event->addToGraph('logo', $logoUrl);
            }
        }
    }

    public function supportedTypes(): array
    {
        return ['Organization'];
    }

    public function priority(): int
    {
        return 80;
    }

    private function resolveLogoUrl(string $logoPath, string $baseUrl): ?string
    {
        $absPath = GeneralUtility::getFileAbsFileName($logoPath);
        if ($absPath === '' || !file_exists($absPath)) {
            return null;
        }

        $webPath = PathUtility::getAbsoluteWebPath($absPath);
        return $baseUrl . '/' . ltrim($webPath, '/');
    }
}
