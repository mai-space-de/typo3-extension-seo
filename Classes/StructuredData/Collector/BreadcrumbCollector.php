<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\StructuredData\Collector;

use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;

final class BreadcrumbCollector implements CollectorInterface
{
    public function __construct(
        private readonly SiteFinder $siteFinder,
    ) {}

    public function collect(StructuredDataCollectionEvent $event): void
    {
        try {
            $rootline = GeneralUtility::makeInstance(RootlineUtility::class, $event->pageUid)->get();
        } catch (\Throwable) {
            return;
        }

        $filteredRootline = array_filter(
            $rootline,
            static fn(array $page): bool => (int) ($page['doktype'] ?? 0) !== 254 && !empty($page['title']),
        );

        if (count($filteredRootline) < 2) {
            return;
        }

        $site = $this->siteFinder->getSiteByPageId($event->pageUid);

        $items = [];
        $position = 1;
        foreach (array_reverse($filteredRootline) as $page) {
            $pageUri = $site->getRouter()->generateUri($page['uid'], []);
            $itemUrl = (string) $pageUri;

            if (!str_starts_with($itemUrl, 'http://') && !str_starts_with($itemUrl, 'https://')) {
                $baseUrl = rtrim((string) $site->getBase(), '/');
                $itemUrl = $baseUrl . '/' . ltrim($itemUrl, '/');
            }

            $items[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $page['title'],
                'item' => $itemUrl,
            ];
        }

        $event->addToGraph('breadcrumb', [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ]);
    }

    public function supportedTypes(): array
    {
        return ['*'];
    }

    public function priority(): int
    {
        return 90;
    }
}
