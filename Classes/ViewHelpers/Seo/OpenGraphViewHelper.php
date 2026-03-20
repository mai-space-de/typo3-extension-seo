<?php

declare(strict_types = 1);

namespace Maispace\MaiSeo\ViewHelpers\Seo;

use Maispace\MaiSeo\Event\AfterOpenGraphRenderedEvent;
use Maispace\MaiSeo\Service\CanonicalService;
use Maispace\MaiSeo\Service\OpenGraphService;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Page\PageRenderer;

class OpenGraphViewHelper extends AbstractSeoViewHelper
{
    private CanonicalService $canonicalService;
    private OpenGraphService $openGraphService;
    private PageRenderer $pageRenderer;
    private EventDispatcherInterface $eventDispatcher;

    public function injectCanonicalService(CanonicalService $service): void
    {
        $this->canonicalService = $service;
    }

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

        // Reuse CanonicalService for og:url resolution to keep behaviour consistent
        $ogUrl = $this->canonicalService->buildCanonicalUrl($pageRecord, $settings);

        $properties = $this->openGraphService->buildProperties($pageRecord, $settings, '', '', $ogUrl);
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
}
