<?php

declare(strict_types = 1);

namespace Maispace\MaispacesSeo\Tests\Unit\ViewHelper;

use Maispace\MaispacesSeo\Service\CanonicalService;
use Maispace\MaispacesSeo\ViewHelpers\Seo\CanonicalViewHelper;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

class CanonicalViewHelperTest extends TestCase
{
    public function testRenderReturnsEmptyStringWhenEnabledIsFalse(): void
    {
        $canonicalService = $this->createMock(CanonicalService::class);
        $canonicalService->expects(self::never())->method('buildCanonicalUrl');

        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer->expects(self::never())->method('addHeaderData');

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $viewHelper = new CanonicalViewHelper();
        $viewHelper->injectCanonicalService($canonicalService);
        $viewHelper->injectPageRenderer($pageRenderer);
        $viewHelper->injectEventDispatcher($eventDispatcher);

        $viewHelper->setArguments(['enabled' => false, 'pageUid' => 0]);

        $result = $viewHelper->render();

        self::assertSame('', $result);
    }

    public function testRenderReturnsEmptyStringWhenPageRecordIsEmpty(): void
    {
        $canonicalService = $this->createMock(CanonicalService::class);
        $canonicalService->expects(self::never())->method('buildCanonicalUrl');

        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer->expects(self::never())->method('addHeaderData');

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->willReturn(null);

        $renderingContext = $this->createMock(RenderingContextInterface::class);
        $renderingContext->method('getAttribute')
            ->with(ServerRequestInterface::class)
            ->willReturn($request);

        $viewHelper = new CanonicalViewHelper();
        $viewHelper->injectCanonicalService($canonicalService);
        $viewHelper->injectPageRenderer($pageRenderer);
        $viewHelper->injectEventDispatcher($eventDispatcher);
        $viewHelper->setRenderingContext($renderingContext);

        $viewHelper->setArguments(['enabled' => true, 'pageUid' => 0]);

        $result = $viewHelper->render();

        self::assertSame('', $result);
    }

    public function testRenderCallsServiceAndAddsCanonicalTagToPageRenderer(): void
    {
        $pageRecord = ['uid' => 1, 'title' => 'Test Page'];
        $canonicalUrl = 'https://example.com/test-page';
        $tag = '<link rel="canonical" href="https://example.com/test-page">';

        $canonicalService = $this->createMock(CanonicalService::class);
        $canonicalService->method('buildCanonicalUrl')->willReturn($canonicalUrl);
        $canonicalService->method('renderTag')->with($canonicalUrl)->willReturn($tag);

        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer->expects(self::once())->method('addHeaderData')->with($tag);

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

        $viewHelper = new CanonicalViewHelper();
        $viewHelper->injectCanonicalService($canonicalService);
        $viewHelper->injectPageRenderer($pageRenderer);
        $viewHelper->injectEventDispatcher($eventDispatcher);
        $viewHelper->setRenderingContext($renderingContext);

        $viewHelper->setArguments(['enabled' => true, 'pageUid' => 0]);

        $result = $viewHelper->render();

        self::assertSame('', $result);
    }

    public function testRenderReturnsEmptyStringWhenServiceReturnsEmptyUrl(): void
    {
        $pageRecord = ['uid' => 1, 'title' => 'Test Page'];

        $canonicalService = $this->createMock(CanonicalService::class);
        $canonicalService->method('buildCanonicalUrl')->willReturn('');

        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer->expects(self::never())->method('addHeaderData');

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

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

        $viewHelper = new CanonicalViewHelper();
        $viewHelper->injectCanonicalService($canonicalService);
        $viewHelper->injectPageRenderer($pageRenderer);
        $viewHelper->injectEventDispatcher($eventDispatcher);
        $viewHelper->setRenderingContext($renderingContext);

        $viewHelper->setArguments(['enabled' => true, 'pageUid' => 0]);

        $result = $viewHelper->render();

        self::assertSame('', $result);
    }
}
