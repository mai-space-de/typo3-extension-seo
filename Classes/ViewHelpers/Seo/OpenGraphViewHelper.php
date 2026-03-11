<?php

declare(strict_types = 1);

namespace Maispace\MaispacesSeo\ViewHelpers\Seo;

use Maispace\MaispacesSeo\Event\AfterOpenGraphRenderedEvent;
use Maispace\MaispacesSeo\Service\OpenGraphService;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class OpenGraphViewHelper extends AbstractViewHelper
{
    private OpenGraphService $openGraphService;
    private PageRenderer $pageRenderer;
    private EventDispatcherInterface $eventDispatcher;

    public function injectOpenGraphService(OpenGraphService $service): void
    {
        $this->openGraphService = $service;
    }

    public function injectPageRenderer(PageRenderer $pageRenderer): void
    {
        $this->pageRenderer = $pageRenderer;
    }

    public function injectEventDispatcher(EventDispatcherInterface $dispatcher): void
    {
        $this->eventDispatcher = $dispatcher;
    }

    public function initializeArguments(): void
    {
        $this->registerArgument('pageUid', 'int', 'Explicit page UID to render Open Graph tags for. 0 = current page.', false, 0);
        $this->registerArgument('twitter', 'bool', 'Set to false to suppress twitter: meta tags.', false, true);
        $this->registerArgument('enabled', 'bool', 'Set to false to suppress all Open Graph output on this page.', false, true);
    }

    public function render(): string
    {
        if ($this->arguments['enabled'] === false) {
            return '';
        }

        $rawPageUid = $this->arguments['pageUid'];
        $pageRecord = $this->resolvePageRecord(is_int($rawPageUid) ? $rawPageUid : 0);
        if ($pageRecord === []) {
            return '';
        }

        $settings = $this->resolveTypoScriptSettings();

        $properties = $this->openGraphService->buildProperties($pageRecord, $settings);
        if ($properties === []) {
            return '';
        }

        // Filter out twitter: properties if twitter argument is false
        if ($this->arguments['twitter'] === false) {
            $properties = array_values(array_filter(
                $properties,
                static fn (array $p): bool => !str_starts_with($p['property'], 'twitter:')
            ));
        }

        /** @var AfterOpenGraphRenderedEvent $event */
        $event = $this->eventDispatcher->dispatch(new AfterOpenGraphRenderedEvent($properties));
        $properties = $event->getProperties();

        foreach ($properties as $meta) {
            $content = $meta['content'];
            if ($content === '') {
                continue;
            }
            $this->pageRenderer->setMetaTag('property', $meta['property'], $content);
        }

        return '';
    }

    /**
     * @return array<string, mixed>
     */
    private function resolvePageRecord(int $pageUid): array
    {
        if ($pageUid > 0) {
            $tsfe = $GLOBALS['TSFE'] ?? null;
            if ($tsfe instanceof TypoScriptFrontendController) {
                /** @var array<string, mixed> $page */
                $page = $tsfe->sys_page->getPage($pageUid) ?: [];

                return $page;
            }

            return [];
        }

        $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
        $frontendController = $request->getAttribute('frontend.controller');
        if ($frontendController instanceof TypoScriptFrontendController) {
            /** @var array<string, mixed> $page */
            $page = $frontendController->page ?? [];

            return $page;
        }

        $tsfe = $GLOBALS['TSFE'] ?? null;
        if ($tsfe instanceof TypoScriptFrontendController) {
            /** @var array<string, mixed> $page */
            $page = $tsfe->page ?? [];

            return $page;
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveTypoScriptSettings(): array
    {
        $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
        $typoscript = $request->getAttribute('frontend.typoscript');
        if ($typoscript instanceof FrontendTypoScript) {
            $setup = $typoscript->getSetupArray();
            $pluginSetup = is_array($setup['plugin.'] ?? null) ? $setup['plugin.'] : [];
            /** @var array<string, mixed> $seoSettings */
            $seoSettings = is_array($pluginSetup['tx_maispace_seo.'] ?? null) ? $pluginSetup['tx_maispace_seo.'] : [];

            return $seoSettings;
        }

        return [];
    }
}
