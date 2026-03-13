<?php

declare(strict_types = 1);

namespace Maispace\MaispacesSeo\ViewHelpers\Seo;

use Maispace\MaispacesSeo\Event\AfterAiRobotsRenderedEvent;
use Maispace\MaispacesSeo\Service\AiRobotsService;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Page\PageRenderer;

class AiRobotsViewHelper extends AbstractSeoViewHelper
{
    private AiRobotsService $aiRobotsService;
    private PageRenderer $pageRenderer;
    private EventDispatcherInterface $eventDispatcher;

    public function injectAiRobotsService(AiRobotsService $service): void
    {
        $this->aiRobotsService = $service;
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
        $this->registerArgument('pageUid', 'int', 'Explicit page UID to render AI robots tags for. 0 = current page.', false, 0);
        $this->registerArgument('enabled', 'bool', 'Set to false to suppress AI robots output on this page.', false, true);
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

        $tags = $this->aiRobotsService->buildTags($pageRecord, $settings);
        if ($tags === []) {
            return '';
        }

        /** @var AfterAiRobotsRenderedEvent $event */
        $event = $this->eventDispatcher->dispatch(new AfterAiRobotsRenderedEvent($tags));
        $tags = $event->getTags();

        foreach ($tags as $tag) {
            if (($tag['name'] ?? '') === '' || $tag['content'] === '') {
                continue;
            }
            $this->pageRenderer->setMetaTag('name', $tag['name'], $tag['content']);
        }

        return '';
    }
}
