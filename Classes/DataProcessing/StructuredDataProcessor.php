<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\DataProcessing;

use Maispace\MaiSeo\StructuredData\StructuredDataService;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

final class StructuredDataProcessor implements DataProcessorInterface
{
    public function __construct(
        private readonly StructuredDataService $structuredDataService,
    ) {}

    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData,
    ): array {
        $pageUid = $cObj->getRequest()->getAttribute('frontend.page.information')?->getId() ?? 0;
        if ($pageUid === 0) {
            return $processedData;
        }

        $graph = $this->structuredDataService->getForPage($pageUid);
        if (empty($graph)) {
            return $processedData;
        }

        $as = $processorConfiguration['as'] ?? 'structuredData';
        $processedData[(string)$as] = [
            'json' => json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
            'graph' => $graph,
        ];

        return $processedData;
    }
}
