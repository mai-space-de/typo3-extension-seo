<?php

declare(strict_types = 1);

namespace Maispace\MaispacesSeo\ViewHelpers\Seo;

use Maispace\MaispacesSeo\Event\AfterMetaDescriptionRenderedEvent;
use Maispace\MaispacesSeo\Service\MetaDescriptionService;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Page\PageRenderer;

class MetaDescriptionViewHelper extends AbstractSeoViewHelper
{
    private MetaDescriptionService $metaDescriptionService;
    private PageRenderer $pageRenderer;
    private EventDispatcherInterface $eventDispatcher;

    public function injectMetaDescriptionService(MetaDescriptionService $service): void
    {
        $this->metaDescriptionService = $service;
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
        $this->registerArgument('pageUid', 'int', 'Explicit page UID to render the meta description for. 0 = current page.', false, 0);
        $this->registerArgument('enabled', 'bool', 'Set to false to suppress meta description output on this page.', false, true);
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

        $description = $this->metaDescriptionService->buildDescription($pageRecord, $settings);
        if ($description === '') {
            return '';
        }

        /** @var AfterMetaDescriptionRenderedEvent $event */
        $event = $this->eventDispatcher->dispatch(new AfterMetaDescriptionRenderedEvent($description));
        $finalDescription = $event->getDescription();

        if ($finalDescription !== '') {
            $this->pageRenderer->setMetaTag('name', 'description', $finalDescription);
        }

        return '';
    }
}
