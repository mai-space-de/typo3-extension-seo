<?php

declare(strict_types=1);

defined('TYPO3') or die();

// Structured-data cache — per-page, invalidated with page cache
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['maiseo_structured_data'] ??= [
    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
    'backend'  => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
    'options'  => ['defaultLifetime' => 86400],
    'groups'   => ['pages'],
];

// DataHandler hook — triggers auto-regeneration when a page record is saved
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][]
    = \Maispace\MaiSeo\Hook\PageRecordSaveHook::class;

// TypoScript — auto-include constants and setup
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptConstants(
    '@import "EXT:mai_seo/Configuration/TypoScript/constants.typoscript"'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup(
    '@import "EXT:mai_seo/Configuration/TypoScript/setup.typoscript"'
);
