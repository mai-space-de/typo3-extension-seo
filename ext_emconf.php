<?php

$EM_CONF[$_EXTKEY] = [
    'title'            => 'Maispace SEO',
    'description'      => 'SEO helpers for TYPO3: JSON-LD structured data and Open Graph meta tags on pages, Fluid ViewHelpers, PSR-14 events, and a backend overview module.',
    'category'         => 'fe',
    'version'          => '1.0.0',
    'state'            => 'stable',
    'author'           => 'Maispace',
    'author_email'     => '',
    'author_company'   => 'Maispace',
    'clearCacheOnLoad' => true,
    'constraints'      => [
        'depends'   => [
            'php'   => '8.2.0-0.0.0',
            'typo3' => '13.4.0-13.9.99',
        ],
        'conflicts' => [],
        'suggests'  => [],
    ],
];
