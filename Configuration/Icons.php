<?php

declare(strict_types=1);

$allIcons = 'EXT:mai_base/Resources/Public/Icons/all-icons/';

return [
    'ext-maispace-mai_seo' => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => 'EXT:mai_seo/Resources/Public/Icons/Extension.svg',
    ],
    'mai-seo' => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $allIcons . 'interface-content-chart--product-data-analysis-analytics-graph-line-business-board-chart--Streamline-Core.svg',
    ],
];
