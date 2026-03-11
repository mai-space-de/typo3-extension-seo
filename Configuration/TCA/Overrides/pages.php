<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

$llPrefix = 'LLL:EXT:maispace_seo/Resources/Private/Language/locallang_tca.xlf:';
$allowedImageTypes = $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] ?? 'gif,jpg,jpeg,png,webp,svg';

$newColumns = [
    'tx_maispace_seo_jsonld_type' => [
        'exclude' => true,
        'label'   => $llPrefix . 'pages.tx_maispace_seo_jsonld_type',
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
    'tx_maispace_seo_jsonld_name' => [
        'exclude' => true,
        'label'   => $llPrefix . 'pages.tx_maispace_seo_jsonld_name',
        'config'  => [
            'type' => 'input',
            'size' => 50,
            'max'  => 255,
            'eval' => 'trim',
        ],
    ],
    'tx_maispace_seo_jsonld_description' => [
        'exclude' => true,
        'label'   => $llPrefix . 'pages.tx_maispace_seo_jsonld_description',
        'config'  => [
            'type' => 'text',
            'rows' => 4,
            'cols' => 50,
        ],
    ],
    'tx_maispace_seo_jsonld_image' => [
        'exclude' => true,
        'label'   => $llPrefix . 'pages.tx_maispace_seo_jsonld_image',
        'config'  => ExtensionManagementUtility::getFileFieldTCAConfig(
            'tx_maispace_seo_jsonld_image',
            ['maxitems' => 1, 'minitems' => 0],
            $allowedImageTypes
        ),
    ],
    'tx_maispace_seo_jsonld_author' => [
        'exclude' => true,
        'label'   => $llPrefix . 'pages.tx_maispace_seo_jsonld_author',
        'config'  => [
            'type' => 'input',
            'size' => 50,
            'max'  => 255,
            'eval' => 'trim',
        ],
    ],
    'tx_maispace_seo_jsonld_date_published' => [
        'exclude' => true,
        'label'   => $llPrefix . 'pages.tx_maispace_seo_jsonld_date_published',
        'config'  => [
            'type'     => 'datetime',
            'format'   => 'datetime',
            'default'  => 0,
        ],
    ],
    'tx_maispace_seo_jsonld_date_modified' => [
        'exclude' => true,
        'label'   => $llPrefix . 'pages.tx_maispace_seo_jsonld_date_modified',
        'config'  => [
            'type'    => 'datetime',
            'format'  => 'datetime',
            'default' => 0,
        ],
    ],
    'tx_maispace_seo_jsonld_custom' => [
        'exclude' => true,
        'label'   => $llPrefix . 'pages.tx_maispace_seo_jsonld_custom',
        'config'  => [
            'type' => 'text',
            'rows' => 6,
            'cols' => 50,
        ],
    ],
    'tx_maispace_seo_og_title' => [
        'exclude' => true,
        'label'   => $llPrefix . 'pages.tx_maispace_seo_og_title',
        'config'  => [
            'type' => 'input',
            'size' => 50,
            'max'  => 255,
            'eval' => 'trim',
        ],
    ],
    'tx_maispace_seo_og_description' => [
        'exclude' => true,
        'label'   => $llPrefix . 'pages.tx_maispace_seo_og_description',
        'config'  => [
            'type' => 'text',
            'rows' => 4,
            'cols' => 50,
        ],
    ],
    'tx_maispace_seo_og_image' => [
        'exclude' => true,
        'label'   => $llPrefix . 'pages.tx_maispace_seo_og_image',
        'config'  => ExtensionManagementUtility::getFileFieldTCAConfig(
            'tx_maispace_seo_og_image',
            ['maxitems' => 1, 'minitems' => 0],
            $allowedImageTypes
        ),
    ],
    'tx_maispace_seo_og_type' => [
        'exclude' => true,
        'label'   => $llPrefix . 'pages.tx_maispace_seo_og_type',
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
    'tx_maispace_seo_twitter_card' => [
        'exclude' => true,
        'label'   => $llPrefix . 'pages.tx_maispace_seo_twitter_card',
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
    'tx_maispace_seo_twitter_image' => [
        'exclude' => true,
        'label'   => $llPrefix . 'pages.tx_maispace_seo_twitter_image',
        'config'  => ExtensionManagementUtility::getFileFieldTCAConfig(
            'tx_maispace_seo_twitter_image',
            ['maxitems' => 1, 'minitems' => 0],
            $allowedImageTypes
        ),
    ],
];

ExtensionManagementUtility::addTCAcolumns('pages', $newColumns);

ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    '--div--;' . $llPrefix . 'tab.seo,'
    . '--palette--;' . $llPrefix . 'tab.seo.jsonld;tx_maispace_seo_jsonld,'
    . 'tx_maispace_seo_jsonld_type,'
    . 'tx_maispace_seo_jsonld_name,'
    . 'tx_maispace_seo_jsonld_description,'
    . 'tx_maispace_seo_jsonld_image,'
    . 'tx_maispace_seo_jsonld_author,'
    . 'tx_maispace_seo_jsonld_date_published,'
    . 'tx_maispace_seo_jsonld_date_modified,'
    . 'tx_maispace_seo_jsonld_custom,'
    . '--palette--;' . $llPrefix . 'tab.seo.opengraph;tx_maispace_seo_og,'
    . 'tx_maispace_seo_og_title,'
    . 'tx_maispace_seo_og_description,'
    . 'tx_maispace_seo_og_image,'
    . 'tx_maispace_seo_og_type,'
    . 'tx_maispace_seo_twitter_card,'
    . 'tx_maispace_seo_twitter_image',
    '',
    'after:title'
);
