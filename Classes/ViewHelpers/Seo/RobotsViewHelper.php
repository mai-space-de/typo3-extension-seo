<?php

declare(strict_types = 1);

namespace Maispace\MaiSeo\ViewHelpers\Seo;

use Maispace\MaiSeo\Event\AfterRobotsRenderedEvent;
use Maispace\MaiSeo\Service\RobotsService;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Page\PageRenderer;

class RobotsViewHelper extends AbstractSeoViewHelper
{
    private RobotsService $robotsService;
    private PageRenderer $pageRenderer;
    private EventDispatcherInterface $eventDispatcher;

    public function injectRobotsService(RobotsService $service): void
    {
        $this->robotsService = $service;
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
        $this->registerArgument('pageUid', 'int', 'Explicit page UID to render the robots tag for. 0 = current page.', false, 0);
        $this->registerArgument('enabled', 'bool', 'Set to false to suppress robots meta tag output on this page.', false, true);
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

        $directives = $this->robotsService->buildDirectives($pageRecord, $settings);
        if ($directives === '') {
            return '';
        }

        $tag = $this->robotsService->renderTag($directives);

        /** @var AfterRobotsRenderedEvent $event */
        $event = $this->eventDispatcher->dispatch(new AfterRobotsRenderedEvent($tag));
        $tag = $event->getTag();

        if ($tag !== '') {
            // Extract content from the (possibly listener-modified) tag for PageRenderer deduplication
            $content = $directives;
            if (preg_match('/content="([^"]*)"/i', $tag, $matches) === 1) {
                $content = $matches[1];
            }
            $this->pageRenderer->setMetaTag('name', 'robots', $content);
        }

        return '';
    }
}
