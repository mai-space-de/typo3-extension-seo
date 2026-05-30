<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\MetaTag;

use Maispace\MaiSeo\StructuredData\StructuredDataProviderInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Page\PageRenderer;

/**
 * Injects JSON-LD into the page head when injectViaPageRenderer is enabled.
 */
final readonly class StructuredDataInjector
{
    public function __construct(
        private StructuredDataProviderInterface $structuredDataService,
        private PageRenderer $pageRenderer,
    ) {}

    /**
     * @param array{request: ServerRequestInterface} $params
     */
    public function generate(array $params): void
    {
        /** @var ServerRequestInterface $request */
        $request = $params['request'];

        if (!$this->isInjectionEnabled($request)) {
            return;
        }

        $pageUid = $request->getAttribute('frontend.page.information')?->getId() ?? 0;
        if ($pageUid === 0) {
            return;
        }

        $graph = $this->structuredDataService->getForPage($pageUid);
        if ($graph === []) {
            return;
        }

        $json = json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($json === false) {
            return;
        }

        $this->pageRenderer->addHeaderData(
            '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>',
        );
    }

    private function isInjectionEnabled(ServerRequestInterface $request): bool
    {
        $frontendTypoScript = $request->getAttribute('frontend.typoscript');
        if (!is_array($frontendTypoScript)) {
            return true;
        }

        $settings = $frontendTypoScript['plugin.']['tx_maiseo.']['settings.']['structuredData.'] ?? [];

        return (bool) ($settings['injectViaPageRenderer'] ?? true);
    }
}
