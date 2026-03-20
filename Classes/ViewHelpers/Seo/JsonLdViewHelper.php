<?php

declare(strict_types = 1);

namespace Maispace\MaiSeo\ViewHelpers\Seo;

use Maispace\MaiSeo\Event\AfterJsonLdRenderedEvent;
use Maispace\MaiSeo\Service\JsonLdService;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Page\PageRenderer;

class JsonLdViewHelper extends AbstractSeoViewHelper
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
}
