<?php

declare(strict_types=1);

return [
    'maispace_seo' => [
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
                'target' => \Maispace\MaispacesSeo\Controller\Backend\SeoController::class . '::indexAction',
            ],
            'statistics' => [
                'target' => \Maispace\MaispacesSeo\Controller\Backend\SeoController::class . '::statisticsAction',
            ],
        ],
    ],
];
