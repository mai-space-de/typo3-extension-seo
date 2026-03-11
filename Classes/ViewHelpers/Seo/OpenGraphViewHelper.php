<?php

declare(strict_types=1);

namespace Maispace\MaispacesSeo\ViewHelpers\Seo;

use Maispace\MaispacesSeo\Event\AfterOpenGraphRenderedEvent;
use Maispace\MaispacesSeo\Service\OpenGraphService;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
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

        $pageRecord = $this->resolvePageRecord((int)$this->arguments['pageUid']);
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
            $content = (string)($meta['content'] ?? '');
            if ($content === '') {
                continue;
            }
            $this->pageRenderer->setMetaTag('property', (string)$meta['property'], $content);
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
