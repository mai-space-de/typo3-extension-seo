<?php

declare(strict_types = 1);

namespace Maispace\MaispacesSeo\ViewHelpers\Seo;

use Maispace\MaispacesSeo\Event\AfterJsonLdRenderedEvent;
use Maispace\MaispacesSeo\Service\JsonLdService;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class JsonLdViewHelper extends AbstractViewHelper
{
    private JsonLdService $jsonLdService;
    private PageRenderer $pageRenderer;
    private EventDispatcherInterface $eventDispatcher;

    public function injectJsonLdService(JsonLdService $service): void
    {
        $this->jsonLdService = $service;
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
        $this->registerArgument('pageUid', 'int', 'Explicit page UID to render JSON-LD for. 0 = current page.', false, 0);
        $this->registerArgument('enabled', 'bool', 'Set to false to suppress JSON-LD output on this page.', false, true);
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

        $schema = $this->jsonLdService->buildSchema($pageRecord, $settings);
        if ($schema === []) {
            return '';
        }

        $script = $this->jsonLdService->renderScript($schema);

        /** @var AfterJsonLdRenderedEvent $event */
        $event = $this->eventDispatcher->dispatch(new AfterJsonLdRenderedEvent($script));
        $script = $event->getScript();

        if ($script !== '') {
            $this->pageRenderer->addHeaderData($script);
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
