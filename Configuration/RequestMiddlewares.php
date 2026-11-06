<?php

declare(strict_types=1);

use Maispace\MaiSeo\Middleware\LanguageRedirectMiddleware;

return [
    'frontend' => [
        'maispace/mai-seo/language-redirect' => [
            'target' => LanguageRedirectMiddleware::class,
            'after' => [
                'typo3/cms-frontend/site',
            ],
            'before' => [
                'typo3/cms-frontend/base-redirect-resolver',
                'typo3/cms-frontend/page-resolver',
            ],
        ],
        'maispace/mai-seo/analytics-collect' => [
            'target' => \Maispace\MaiSeo\Middleware\AnalyticsCollectMiddleware::class,
            'after' => [
                'typo3/cms-frontend/site',
                'typo3/cms-frontend/backend-user-authentication',
            ],
            'before' => [
                'typo3/cms-frontend/page-resolver',
            ],
        ],
    ],
];
