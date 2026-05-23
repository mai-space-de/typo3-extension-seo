<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$lang = static function (string $key): string {
    return 'LLL:EXT:mai_seo/Resources/Private/Language/Default/locallang_tca.xlf:' . $key;
};

ExtensionManagementUtility::addTCAcolumns(
    'pages',
    [
        'tx_maiseo_schema_type' => [
            'label' => $lang('pages.tx_maiseo_schema_type'),
            'description' => $lang('pages.tx_maiseo_schema_type.description'),
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => $lang('pages.tx_maiseo_schema_type.auto'), 'value' => ''],
                    ['label' => 'Organization', 'value' => 'Organization'],
                    ['label' => 'WebPage', 'value' => 'WebPage'],
                    ['label' => 'AboutPage', 'value' => 'AboutPage'],
                    ['label' => 'ContactPage', 'value' => 'ContactPage'],
                    ['label' => 'Article', 'value' => 'Article'],
                    ['label' => 'BreadcrumbList', 'value' => 'BreadcrumbList'],
                    ['label' => 'Event', 'value' => 'Event'],
                    ['label' => 'FAQPage', 'value' => 'FAQPage'],
                    ['label' => 'LocalBusiness', 'value' => 'LocalBusiness'],
                    ['label' => 'Person', 'value' => 'Person'],
                    ['label' => 'Product', 'value' => 'Product'],
                    ['label' => 'WebSite', 'value' => 'WebSite'],
                    ['label' => 'CollectionPage', 'value' => 'CollectionPage'],
                    ['label' => 'JobPosting', 'value' => 'JobPosting'],
                ],
                'default' => '',
            ],
        ],
        'tx_maiseo_og_title' => [
            'label' => $lang('pages.tx_maiseo_og_title'),
            'description' => $lang('pages.tx_maiseo_og_title.description'),
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
            ],
        ],
        'tx_maiseo_og_description' => [
            'label' => $lang('pages.tx_maiseo_og_description'),
            'description' => $lang('pages.tx_maiseo_og_description.description'),
            'config' => [
                'type' => 'text',
                'rows' => 3,
                'cols' => 50,
            ],
        ],
        'tx_maiseo_og_image' => [
            'label' => $lang('pages.tx_maiseo_og_image'),
            'description' => $lang('pages.tx_maiseo_og_image.description'),
            'config' => [
                'type' => 'file',
                'allowed' => 'common-image-types',
                'maxitems' => 1,
                'appearance' => [
                    'createNewRelationLinkTitle' => $lang('pages.tx_maiseo_og_image.addFile'),
                ],
            ],
        ],
        'tx_maiseo_structured_data' => [
            'label' => $lang('pages.tx_maiseo_structured_data'),
            'description' => $lang('pages.tx_maiseo_structured_data.description'),
            'config' => [
                'type' => 'user',
                'renderType' => 'maiseoStructuredDataTree',
                'default' => '',
            ],
        ],
    ]
);

$GLOBALS['TCA']['pages']['palettes']['mai_seo'] = [
    'label' => $lang('palette.mai_seo'),
    'showitem' => 'tx_maiseo_og_title, tx_maiseo_og_description, --linebreak--, tx_maiseo_og_image, tx_maiseo_schema_type',
];

ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    '--div--;' . $lang('tab.seo_overrides') . ', --palette--;;mai_seo, tx_maiseo_structured_data',
    '1,4',
    'after:keywords'
);
