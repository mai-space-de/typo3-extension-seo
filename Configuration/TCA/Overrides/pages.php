<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

$llPrefix = 'LLL:EXT:mai_seo/Resources/Private/Language/locallang_tca.xlf:';

$newColumns = [
    'tx_maiseo_jsonld_type' => [
        'exclude' => true,
        'label'   => $llPrefix . 'pages.tx_maiseo_jsonld_type',
        'config'  => [
            'type'       => 'select',
            'renderType' => 'selectSingle',
            'items'      => [
                ['label' => '', 'value' => ''],
                ['label' => 'WebPage', 'value' => 'WebPage'],
                ['label' => 'Article', 'value' => 'Article'],
                ['label' => 'Product', 'value' => 'Product'],
                ['label' => 'FAQPage', 'value' => 'FAQPage'],
                ['label' => 'BlogPosting', 'value' => 'BlogPosting'],
                ['label' => 'NewsArticle', 'value' => 'NewsArticle'],
                ['label' => 'Event', 'value' => 'Event'],
                ['label' => 'Person', 'value' => 'Person'],
                ['label' => 'Organization', 'value' => 'Organization'],
                ['label' => 'LocalBusiness', 'value' => 'LocalBusiness'],
            ],
            'default'    => '',
        ],
    ],
    'tx_maiseo_jsonld_name' => [
        'exclude' => true,
        'label'   => $llPrefix . 'pages.tx_maiseo_jsonld_name',
        'config'  => [
            'type' => 'input',
            'size' => 50,
            'max'  => 255,
            'eval' => 'trim',
        ],
    ],
    'tx_maiseo_jsonld_description' => [
        'exclude' => true,
        'label'   => $llPrefix . 'pages.tx_maiseo_jsonld_description',
        'config'  => [
            'type' => 'text',
            'rows' => 4,
            'cols' => 50,
        ],
    ],
    'tx_maiseo_jsonld_image' => [
        'exclude' => true,
        'label'   => $llPrefix . 'pages.tx_maiseo_jsonld_image',
        'config'  => [
            'type'     => 'file',
            'maxitems' => 1,
            'minitems' => 0,
            'allowed'  => 'common-image-types',
        ],
    ],
    'tx_maiseo_jsonld_author' => [
        'exclude' => true,
        'label'   => $llPrefix . 'pages.tx_maiseo_jsonld_author',
        'config'  => [
            'type' => 'input',
            'size' => 50,
            'max'  => 255,
            'eval' => 'trim',
        ],
    ],
    'tx_maiseo_jsonld_date_published' => [
        'exclude' => true,
        'label'   => $llPrefix . 'pages.tx_maiseo_jsonld_date_published',
        'config'  => [
            'type'     => 'datetime',
            'format'   => 'datetime',
            'default'  => 0,
        ],
    ],
    'tx_maiseo_jsonld_date_modified' => [
        'exclude' => true,
        'label'   => $llPrefix . 'pages.tx_maiseo_jsonld_date_modified',
        'config'  => [
            'type'    => 'datetime',
            'format'  => 'datetime',
            'default' => 0,
        ],
    ],
    'tx_maiseo_jsonld_custom' => [
        'exclude' => true,
        'label'   => $llPrefix . 'pages.tx_maiseo_jsonld_custom',
        'config'  => [
            'type' => 'text',
            'rows' => 6,
            'cols' => 50,
        ],
    ],
    'tx_maiseo_og_title' => [
        'exclude' => true,
        'label'   => $llPrefix . 'pages.tx_maiseo_og_title',
        'config'  => [
            'type' => 'input',
            'size' => 50,
            'max'  => 255,
            'eval' => 'trim',
        ],
    ],
    'tx_maiseo_og_description' => [
        'exclude' => true,
        'label'   => $llPrefix . 'pages.tx_maiseo_og_description',
        'config'  => [
            'type' => 'text',
            'rows' => 4,
            'cols' => 50,
        ],
    ],
    'tx_maiseo_og_image' => [
        'exclude' => true,
        'label'   => $llPrefix . 'pages.tx_maiseo_og_image',
        'config'  => [
            'type'     => 'file',
            'maxitems' => 1,
            'minitems' => 0,
            'allowed'  => 'common-image-types',
        ],
    ],
    'tx_maiseo_og_type' => [
        'exclude' => true,
        'label'   => $llPrefix . 'pages.tx_maiseo_og_type',
        'config'  => [
            'type'       => 'select',
            'renderType' => 'selectSingle',
            'items'      => [
                ['label' => 'website', 'value' => 'website'],
                ['label' => 'article', 'value' => 'article'],
                ['label' => 'product', 'value' => 'product'],
                ['label' => 'book', 'value' => 'book'],
                ['label' => 'profile', 'value' => 'profile'],
                ['label' => 'music.song', 'value' => 'music.song'],
                ['label' => 'video.movie', 'value' => 'video.movie'],
            ],
            'default'    => 'website',
        ],
    ],
    'tx_maiseo_twitter_card' => [
        'exclude' => true,
        'label'   => $llPrefix . 'pages.tx_maiseo_twitter_card',
        'config'  => [
            'type'       => 'select',
            'renderType' => 'selectSingle',
            'items'      => [
                ['label' => 'summary', 'value' => 'summary'],
                ['label' => 'summary_large_image', 'value' => 'summary_large_image'],
                ['label' => 'app', 'value' => 'app'],
                ['label' => 'player', 'value' => 'player'],
            ],
            'default'    => 'summary',
        ],
    ],
    'tx_maiseo_twitter_image' => [
        'exclude' => true,
        'label'   => $llPrefix . 'pages.tx_maiseo_twitter_image',
        'config'  => [
            'type'     => 'file',
            'maxitems' => 1,
            'minitems' => 0,
            'allowed'  => 'common-image-types',
        ],
    ],
    'tx_maiseo_canonical_url' => [
        'exclude' => true,
        'label'   => $llPrefix . 'pages.tx_maiseo_canonical_url',
        'config'  => [
            'type' => 'input',
            'size' => 50,
            'max'  => 2048,
            'eval' => 'trim',
        ],
    ],
    'tx_maiseo_robots_noindex' => [
        'exclude' => true,
        'label'   => $llPrefix . 'pages.tx_maiseo_robots_noindex',
        'config'  => [
            'type'    => 'check',
            'default' => 0,
        ],
    ],
    'tx_maiseo_robots_nofollow' => [
        'exclude' => true,
        'label'   => $llPrefix . 'pages.tx_maiseo_robots_nofollow',
        'config'  => [
            'type'    => 'check',
            'default' => 0,
        ],
    ],
    'tx_maiseo_robots_noarchive' => [
        'exclude' => true,
        'label'   => $llPrefix . 'pages.tx_maiseo_robots_noarchive',
        'config'  => [
            'type'    => 'check',
            'default' => 0,
        ],
    ],
    'tx_maiseo_meta_description' => [
        'exclude' => true,
        'label'   => $llPrefix . 'pages.tx_maiseo_meta_description',
        'config'  => [
            'type' => 'text',
            'rows' => 3,
            'cols' => 50,
        ],
    ],
    'tx_maiseo_ai_noindex' => [
        'exclude' => true,
        'label'   => $llPrefix . 'pages.tx_maiseo_ai_noindex',
        'config'  => [
            'type'    => 'check',
            'default' => 0,
        ],
    ],
];

ExtensionManagementUtility::addTCAcolumns('pages', $newColumns);

// Define palettes so that the group labels appear in the form engine
ExtensionManagementUtility::addFieldsToPalette(
    'pages',
    'tx_maiseo_jsonld',
    'tx_maiseo_jsonld_type, tx_maiseo_jsonld_name, tx_maiseo_jsonld_description, --linebreak--, '
    . 'tx_maiseo_jsonld_image, tx_maiseo_jsonld_author, --linebreak--, '
    . 'tx_maiseo_jsonld_date_published, tx_maiseo_jsonld_date_modified, --linebreak--, '
    . 'tx_maiseo_jsonld_custom'
);

ExtensionManagementUtility::addFieldsToPalette(
    'pages',
    'tx_maiseo_og',
    'tx_maiseo_og_title, tx_maiseo_og_description, --linebreak--, '
    . 'tx_maiseo_og_image, tx_maiseo_og_type, --linebreak--, '
    . 'tx_maiseo_twitter_card, tx_maiseo_twitter_image'
);

ExtensionManagementUtility::addFieldsToPalette(
    'pages',
    'tx_maiseo_advanced',
    'tx_maiseo_meta_description, --linebreak--, '
    . 'tx_maiseo_canonical_url, --linebreak--, '
    . 'tx_maiseo_robots_noindex, tx_maiseo_robots_nofollow, tx_maiseo_robots_noarchive, --linebreak--, '
    . 'tx_maiseo_ai_noindex'
);

ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    '--div--;' . $llPrefix . 'tab.seo,'
    . '--palette--;' . $llPrefix . 'tab.seo.jsonld;tx_maiseo_jsonld,'
    . '--palette--;' . $llPrefix . 'tab.seo.opengraph;tx_maiseo_og,'
    . '--palette--;' . $llPrefix . 'tab.seo.advanced;tx_maiseo_advanced',
    '',
    'after:title'
);
