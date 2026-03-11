<?php

declare(strict_types=1);

namespace Maispace\MaispacesSeo\ViewHelpers\Seo;

use Maispace\MaispacesSeo\Event\AfterJsonLdRenderedEvent;
use Maispace\MaispacesSeo\Service\JsonLdService;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
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

        $pageRecord = $this->resolvePageRecord((int)$this->arguments['pageUid']);
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
            return $GLOBALS['TSFE']->sys_page->getPage($pageUid) ?: [];
        }

        $request = $this->renderingContext->getRequest();
        $frontendController = $request->getAttribute('frontend.controller');
        if ($frontendController !== null && isset($frontendController->page)) {
            return (array)$frontendController->page;
        }

        return isset($GLOBALS['TSFE']) ? (array)$GLOBALS['TSFE']->page : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveTypoScriptSettings(): array
    {
        $request = $this->renderingContext->getRequest();
        $typoscript = $request->getAttribute('frontend.typoscript');
        if ($typoscript !== null) {
            $setup = $typoscript->getSetupArray();

            return $setup['plugin.']['tx_maispace_seo.'] ?? [];
        }

        return $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_maispace_seo.'] ?? [];
    }
}
