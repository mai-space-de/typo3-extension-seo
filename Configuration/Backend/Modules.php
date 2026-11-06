<?php

declare(strict_types=1);

use Maispace\MaiSeo\Controller\Backend\AnalyticsBackendController;

return [
    'site_maiseo_analytics' => [
        'parent' => 'site',
        'position' => ['after' => 'site_settings'],
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/site/maiseo-analytics',
        'labels' => 'LLL:EXT:mai_seo/Resources/Private/Language/locallang_mod_analytics.xlf',
        'extensionName' => 'MaiSeo',
        'iconIdentifier' => 'mai-seo-module',
        'controllerActions' => [
            AnalyticsBackendController::class => ['index'],
        ],
    ],
];
