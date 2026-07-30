<?php

declare(strict_types = 1);

namespace Maispace\MaiSeo\Tests\Unit\ViewHelper;

use Maispace\MaiSeo\Service\LlmsTxtService;
use Maispace\MaiSeo\ViewHelpers\Seo\LlmsTxtViewHelper;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

class LlmsTxtViewHelperTest extends TestCase
{
    public function testRenderReturnsEmptyStringWhenEnabledIsFalse(): void
    {
        $llmsTxtService = $this->createMock(LlmsTxtService::class);
        $llmsTxtService->expects(self::never())->method('buildUrl');

        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer->expects(self::never())->method('addHeaderData');

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $viewHelper = new LlmsTxtViewHelper();
        $viewHelper->injectLlmsTxtService($llmsTxtService);
        $viewHelper->injectPageRenderer($pageRenderer);
        $viewHelper->injectEventDispatcher($eventDispatcher);

        $viewHelper->setArguments(['enabled' => false, 'pageUid' => 0]);

        $result = $viewHelper->render();

        self::assertSame('', $result);
    }

    public function testRenderReturnsEmptyStringWhenPageRecordIsEmpty(): void
    {
        $llmsTxtService = $this->createMock(LlmsTxtService::class);
        $llmsTxtService->expects(self::never())->method('buildUrl');

        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer->expects(self::never())->method('addHeaderData');

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->willReturn(null);

        $renderingContext = $this->createMock(RenderingContextInterface::class);
        $renderingContext->method('getAttribute')
            ->with(ServerRequestInterface::class)
            ->willReturn($request);

        $viewHelper = new LlmsTxtViewHelper();
        $viewHelper->injectLlmsTxtService($llmsTxtService);
        $viewHelper->injectPageRenderer($pageRenderer);
        $viewHelper->injectEventDispatcher($eventDispatcher);
        $viewHelper->setRenderingContext($renderingContext);

        $viewHelper->setArguments(['enabled' => true, 'pageUid' => 0]);

        $result = $viewHelper->render();

        self::assertSame('', $result);
    }

    public function testRenderCallsServiceAndAddsLlmsTxtTagToPageRenderer(): void
    {
        $pageRecord = ['uid' => 1, 'title' => 'Test Page'];
        $url = '/llms.txt';
        $tag = '<link rel="llms-txt" href="/llms.txt" type="text/plain">';

        $llmsTxtService = $this->createMock(LlmsTxtService::class);
        $llmsTxtService->method('buildUrl')->willReturn($url);
        $llmsTxtService->method('renderTag')->with($url)->willReturn($tag);

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

        $viewHelper = new LlmsTxtViewHelper();
        $viewHelper->injectLlmsTxtService($llmsTxtService);
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

        $llmsTxtService = $this->createMock(LlmsTxtService::class);
        $llmsTxtService->method('buildUrl')->willReturn('');

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

        $viewHelper = new LlmsTxtViewHelper();
        $viewHelper->injectLlmsTxtService($llmsTxtService);
        $viewHelper->injectPageRenderer($pageRenderer);
        $viewHelper->injectEventDispatcher($eventDispatcher);
        $viewHelper->setRenderingContext($renderingContext);

        $viewHelper->setArguments(['enabled' => true, 'pageUid' => 0]);

        $result = $viewHelper->render();

        self::assertSame('', $result);
    }
}
