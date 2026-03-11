<?php

declare(strict_types=1);

namespace Maispace\MaispacesSeo\Tests\Unit\ViewHelper;

use Maispace\MaispacesSeo\Service\JsonLdService;
use Maispace\MaispacesSeo\ViewHelpers\Seo\JsonLdViewHelper;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use Psr\Http\Message\ServerRequestInterface;

class JsonLdViewHelperTest extends TestCase
{
    public function testRenderReturnsEmptyStringWhenEnabledIsFalse(): void
    {
        $viewHelper = new JsonLdViewHelper();

        $jsonLdService = $this->createMock(JsonLdService::class);
        $jsonLdService->expects(self::never())->method('buildSchema');

        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer->expects(self::never())->method('addHeaderData');

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $viewHelper->injectJsonLdService($jsonLdService);
        $viewHelper->injectPageRenderer($pageRenderer);
        $viewHelper->injectEventDispatcher($eventDispatcher);

        // Set up arguments with enabled = false
        $viewHelper->setArguments(['enabled' => false, 'pageUid' => 0]);

        $result = $viewHelper->render();

        self::assertSame('', $result);
    }

    public function testRenderCallsServiceAndAddsToPageRenderer(): void
    {
        $pageRecord = ['uid' => 1, 'title' => 'Test Page', 'tx_maispace_seo_jsonld_type' => 'WebPage'];
        $schema = ['@context' => 'https://schema.org', '@type' => 'WebPage', 'name' => 'Test Page'];
        $script = '<script type="application/ld+json">{}</script>';

        $jsonLdService = $this->createMock(JsonLdService::class);
        $jsonLdService->method('buildSchema')->willReturn($schema);
        $jsonLdService->method('renderScript')->with($schema)->willReturn($script);

        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer->expects(self::once())->method('addHeaderData')->with($script);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnArgument(0);

        // Mock the request
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->willReturn(null);

        $renderingContext = $this->createMock(RenderingContextInterface::class);
        $renderingContext->method('getRequest')->willReturn($request);

        $viewHelper = new JsonLdViewHelper();
        $viewHelper->injectJsonLdService($jsonLdService);
        $viewHelper->injectPageRenderer($pageRenderer);
        $viewHelper->injectEventDispatcher($eventDispatcher);
        $viewHelper->setRenderingContext($renderingContext);

        // Provide a page record via GLOBALS fallback
        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->page = $pageRecord;

        $viewHelper->setArguments(['enabled' => true, 'pageUid' => 0]);

        $result = $viewHelper->render();

        self::assertSame('', $result);

        unset($GLOBALS['TSFE']);
    }
}
