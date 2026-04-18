<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\ViewHelpers;

use Maispace\MaiSeo\StructuredData\StructuredDataService;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

final class StructuredDataViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    public function __construct(
        private readonly StructuredDataService $structuredDataService,
    ) {}

    public function initializeArguments(): void
    {
        $this->registerArgument('pageUid', 'int', 'Page UID to render structured data for. Defaults to current page.', false, 0);
    }

    public function render(): string
    {
        $pageUid = (int)$this->arguments['pageUid'];
        if ($pageUid === 0) {
            $pageUid = $GLOBALS['TSFE']?->id ?? 0;
        }
        if ($pageUid === 0) {
            return '';
        }

        $graph = $this->structuredDataService->getForPage($pageUid);
        if (empty($graph)) {
            return '';
        }

        $json = json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        return '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>';
    }
}
