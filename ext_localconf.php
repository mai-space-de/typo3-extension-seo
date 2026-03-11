<?php

declare(strict_types=1);

defined('TYPO3') or die();

call_user_func(static function (): void {
    // Register 'mai' Fluid ViewHelper namespace for SEO ViewHelpers.
    // Appends to the existing 'mai' namespace (e.g. registered by maispace/assets)
    // so that <mai:seo.jsonLd> and <mai:seo.openGraph> are available globally.
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['mai'][] =
        'Maispace\\MaispacesSeo\\ViewHelpers';

    // Register the maispace_seo caching framework cache.
    // Grouped with pages so a page cache flush also clears cached SEO data.
    if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['maispace_seo'])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['maispace_seo'] = [
            'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
            'backend'  => \TYPO3\CMS\Core\Cache\Backend\FileBackend::class,
            'options'  => [
                'defaultLifetime' => 0,
            ],
            'groups'   => ['pages', 'all'],
        ];
    }
});
