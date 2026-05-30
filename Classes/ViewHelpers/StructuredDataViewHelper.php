<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\ViewHelpers;

use Maispace\MaiSeo\StructuredData\StructuredDataService;
use Psr\Http\Message\ServerRequestInterface;
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
        if ($this->isPageRendererInjectionEnabled()) {
            return '';
        }

        $pageUid = (int) $this->arguments['pageUid'];
        if ($pageUid === 0) {
            $pageUid = $this->resolveCurrentPageUid();
        }
        if ($pageUid === 0) {
            return '';
        }

        $graph = $this->structuredDataService->getForPage($pageUid);
        if ($graph === []) {
            return '';
        }

        $json = json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($json === false) {
            return '';
        }

        return '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>';
    }

    private function resolveCurrentPageUid(): int
    {
        $request = $this->resolveRequest();
        if ($request === null) {
            return 0;
        }

        return $request->getAttribute('frontend.page.information')?->getId() ?? 0;
    }

    private function resolveRequest(): ?ServerRequestInterface
    {
        if ($this->renderingContext->hasAttribute(ServerRequestInterface::class)) {
            return $this->renderingContext->getAttribute(ServerRequestInterface::class);
        }

        $globalRequest = $GLOBALS['TYPO3_REQUEST'] ?? null;

        return $globalRequest instanceof ServerRequestInterface ? $globalRequest : null;
    }

    private function isPageRendererInjectionEnabled(): bool
    {
        $request = $this->resolveRequest();
        if ($request === null) {
            return true;
        }

        $frontendTypoScript = $request->getAttribute('frontend.typoscript');
        if (!is_array($frontendTypoScript)) {
            return true;
        }

        $settings = $frontendTypoScript['plugin.']['tx_maiseo.']['settings.']['structuredData.'] ?? [];

        return (bool) ($settings['injectViaPageRenderer'] ?? true);
    }
}
