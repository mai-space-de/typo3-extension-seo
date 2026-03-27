<?php

declare(strict_types = 1);

namespace Maispace\MaiSeo\ViewHelpers\Seo;

use Maispace\MaiSeo\Event\AfterLlmsTxtRenderedEvent;
use Maispace\MaiSeo\Service\LlmsTxtService;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Page\PageRenderer;

class LlmsTxtViewHelper extends AbstractSeoViewHelper
{
    private LlmsTxtService $llmsTxtService;
    private PageRenderer $pageRenderer;
    private EventDispatcherInterface $eventDispatcher;

    public function injectLlmsTxtService(LlmsTxtService $service): void
    {
        $this->llmsTxtService = $service;
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
        $this->registerArgument('pageUid', 'int', 'Explicit page UID to render the llms-txt link for. 0 = current page.', false, 0);
        $this->registerArgument('enabled', 'bool', 'Set to false to suppress llms-txt output on this page.', false, true);
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

        $url = $this->llmsTxtService->buildUrl($pageRecord, $settings);
        if ($url === '') {
            return '';
        }

        $tag = $this->llmsTxtService->renderTag($url);

        /** @var AfterLlmsTxtRenderedEvent $event */
        $event = $this->eventDispatcher->dispatch(new AfterLlmsTxtRenderedEvent($tag));
        $tag = $event->getTag();

        if ($tag !== '') {
            $this->pageRenderer->addHeaderData($tag);
        }

        return '';
    }
}
