<?php

declare(strict_types = 1);

namespace Maispace\MaiSeo\ViewHelpers\Seo;

use Maispace\MaiSeo\Event\AfterCanonicalRenderedEvent;
use Maispace\MaiSeo\Service\CanonicalService;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Page\PageRenderer;

class CanonicalViewHelper extends AbstractSeoViewHelper
{
    private CanonicalService $canonicalService;
    private PageRenderer $pageRenderer;
    private EventDispatcherInterface $eventDispatcher;

    public function injectCanonicalService(CanonicalService $service): void
    {
        $this->canonicalService = $service;
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
        $this->registerArgument('pageUid', 'int', 'Explicit page UID to render the canonical tag for. 0 = current page.', false, 0);
        $this->registerArgument('enabled', 'bool', 'Set to false to suppress canonical output on this page.', false, true);
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

        $url = $this->canonicalService->buildCanonicalUrl($pageRecord, $settings);
        if ($url === '') {
            return '';
        }

        $tag = $this->canonicalService->renderTag($url);

        /** @var AfterCanonicalRenderedEvent $event */
        $event = $this->eventDispatcher->dispatch(new AfterCanonicalRenderedEvent($tag));
        $tag = $event->getTag();

        if ($tag !== '') {
            $this->pageRenderer->addHeaderData($tag);
        }

        return '';
    }
}
