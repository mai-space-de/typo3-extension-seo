<?php

declare(strict_types = 1);

namespace Maispace\MaiSeo\Tests\Unit\ViewHelper;

use Maispace\MaiSeo\Service\JsonLdService;
use Maispace\MaiSeo\ViewHelpers\Seo\JsonLdViewHelper;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

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
        $pageRecord = ['uid' => 1, 'title' => 'Test Page', 'tx_maiseo_jsonld_type' => 'WebPage'];
        $schema = ['@context' => 'https://schema.org', '@type' => 'WebPage', 'name' => 'Test Page'];
        $script = '<script type="application/ld+json">{}</script>';

        $jsonLdService = $this->createMock(JsonLdService::class);
        $jsonLdService->method('buildSchema')->willReturn($schema);
        $jsonLdService->method('renderScript')->with($schema)->willReturn($script);

        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer->expects(self::once())->method('addHeaderData')->with($script);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnArgument(0);

        $tsfeMock = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->disableOriginalConstructor()
            ->getMock();
        $tsfeMock->page = $pageRecord;

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')
            ->willReturnCallback(
                static fn (string $attr) => $attr === 'frontend.controller' ? $tsfeMock : null
            );

        $renderingContext = $this->createMock(RenderingContextInterface::class);
        $renderingContext->method('getAttribute')
            ->with(ServerRequestInterface::class)
            ->willReturn($request);

        $viewHelper = new JsonLdViewHelper();
        $viewHelper->injectJsonLdService($jsonLdService);
        $viewHelper->injectPageRenderer($pageRenderer);
        $viewHelper->injectEventDispatcher($eventDispatcher);
        $viewHelper->setRenderingContext($renderingContext);

        $viewHelper->setArguments(['enabled' => true, 'pageUid' => 0]);

        $result = $viewHelper->render();

        self::assertSame('', $result);
    }
}
