<?php

declare(strict_types=1);

return [
    'mai_seo' => [
        'parent'         => 'web',
        'position'       => ['after' => 'web_info'],
        'access'         => 'user',
        'workspaces'     => 'live',
        'iconIdentifier' => 'maispace-seo',
        'path'           => '/module/maispace/seo',
        'labels'         => 'LLL:EXT:maispace_seo/Resources/Private/Language/locallang_mod.xlf',
        'packageName'    => 'maispace/seo',
        'routes'         => [
            '_default' => [
                'target' => \Maispace\MaiSeo\Controller\Backend\SeoController::class . '::indexAction',
            ],
            'statistics' => [
                'target' => \Maispace\MaiSeo\Controller\Backend\SeoController::class . '::statisticsAction',
            ],
        ],
    ],
];
