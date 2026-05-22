<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\StructuredData\Collector;

use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;

final class BreadcrumbCollector implements CollectorInterface
{
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

        $items = [];
        $position = 1;
        foreach (array_reverse($filteredRootline) as $page) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $page['title'],
                'item' => 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/?id=' . $page['uid'],
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
