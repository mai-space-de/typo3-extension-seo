<?php

declare(strict_types=1);

return [
    'maiSeoAnalyticsOverview' => [
        'title' => 'LLL:EXT:mai_seo/Resources/Private/Language/locallang_analytics.xlf:dashboard.preset.title',
        'description' => 'LLL:EXT:mai_seo/Resources/Private/Language/locallang_analytics.xlf:dashboard.preset.description',
        'iconIdentifier' => 'ext-maispace-mai_seo',
        'defaultWidgets' => [
            'maiSeoTrafficGraph',
            'maiSeoSitePerformance',
            'maiSeoTopPages',
            'maiSeoTrafficChannel',
            'maiSeoTrafficDevices',
            'maiSeoTrafficBrowsers',
            'maiSeoTrafficLanguages',
        ],
        'showInWizard' => true,
    ],
];
