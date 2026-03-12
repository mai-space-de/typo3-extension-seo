<?php

declare(strict_types = 1);

namespace Maispace\MaispacesSeo\Tests\Unit\ViewHelper;

use Maispace\MaispacesSeo\Service\RobotsService;
use Maispace\MaispacesSeo\ViewHelpers\Seo\RobotsViewHelper;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

class RobotsViewHelperTest extends TestCase
{
    public function testRenderReturnsEmptyStringWhenEnabledIsFalse(): void
    {
        $robotsService = $this->createMock(RobotsService::class);
        $robotsService->expects(self::never())->method('buildDirectives');

        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer->expects(self::never())->method('setMetaTag');

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $viewHelper = new RobotsViewHelper();
        $viewHelper->injectRobotsService($robotsService);
        $viewHelper->injectPageRenderer($pageRenderer);
        $viewHelper->injectEventDispatcher($eventDispatcher);

        $viewHelper->setArguments(['enabled' => false, 'pageUid' => 0]);

        $result = $viewHelper->render();

        self::assertSame('', $result);
    }

    public function testRenderReturnsEmptyStringWhenPageRecordIsEmpty(): void
    {
        $robotsService = $this->createMock(RobotsService::class);
        $robotsService->expects(self::never())->method('buildDirectives');

        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer->expects(self::never())->method('setMetaTag');

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->willReturn(null);

        $renderingContext = $this->createMock(RenderingContextInterface::class);
        $renderingContext->method('getAttribute')
            ->with(ServerRequestInterface::class)
            ->willReturn($request);

        $viewHelper = new RobotsViewHelper();
        $viewHelper->injectRobotsService($robotsService);
        $viewHelper->injectPageRenderer($pageRenderer);
        $viewHelper->injectEventDispatcher($eventDispatcher);
        $viewHelper->setRenderingContext($renderingContext);

        $viewHelper->setArguments(['enabled' => true, 'pageUid' => 0]);

        $result = $viewHelper->render();

        self::assertSame('', $result);
    }

    public function testRenderCallsServiceAndSetsMetaTagOnPageRenderer(): void
    {
        $pageRecord = ['uid' => 1, 'title' => 'Test Page'];
        $directives = 'index, follow';
        $tag = '<meta name="robots" content="index, follow">';

        $robotsService = $this->createMock(RobotsService::class);
        $robotsService->method('buildDirectives')->willReturn($directives);
        $robotsService->expects(self::once())->method('renderTag')->with($directives)->willReturn($tag);

        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer->expects(self::once())->method('setMetaTag')->with('name', 'robots', 'index, follow');

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

        $viewHelper = new RobotsViewHelper();
        $viewHelper->injectRobotsService($robotsService);
        $viewHelper->injectPageRenderer($pageRenderer);
        $viewHelper->injectEventDispatcher($eventDispatcher);
        $viewHelper->setRenderingContext($renderingContext);

        $viewHelper->setArguments(['enabled' => true, 'pageUid' => 0]);

        $result = $viewHelper->render();

        self::assertSame('', $result);
    }

    public function testRenderReturnsEmptyStringWhenServiceReturnsEmptyDirectives(): void
    {
        $pageRecord = ['uid' => 1, 'title' => 'Test Page'];

        $robotsService = $this->createMock(RobotsService::class);
        $robotsService->method('buildDirectives')->willReturn('');

        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer->expects(self::never())->method('setMetaTag');

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

        $viewHelper = new RobotsViewHelper();
        $viewHelper->injectRobotsService($robotsService);
        $viewHelper->injectPageRenderer($pageRenderer);
        $viewHelper->injectEventDispatcher($eventDispatcher);
        $viewHelper->setRenderingContext($renderingContext);

        $viewHelper->setArguments(['enabled' => true, 'pageUid' => 0]);

        $result = $viewHelper->render();

        self::assertSame('', $result);
    }

    public function testRenderExtractsModifiedContentFromListenerTag(): void
    {
        $pageRecord = ['uid' => 1, 'title' => 'Test Page'];
        $directives = 'index, follow';
        $originalTag = '<meta name="robots" content="index, follow">';
        $modifiedTag = '<meta name="robots" content="noindex, nofollow, noarchive">';

        $robotsService = $this->createMock(RobotsService::class);
        $robotsService->method('buildDirectives')->willReturn($directives);
        $robotsService->method('renderTag')->with($directives)->willReturn($originalTag);

        // Expect setMetaTag to be called with the content from the listener-modified tag
        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer->expects(self::once())
            ->method('setMetaTag')
            ->with('name', 'robots', 'noindex, nofollow, noarchive');

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnCallback(
            static function (object $event) use ($modifiedTag): object {
                if (method_exists($event, 'setTag')) {
                    $event->setTag($modifiedTag);
                }

                return $event;
            }
        );

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

        $viewHelper = new RobotsViewHelper();
        $viewHelper->injectRobotsService($robotsService);
        $viewHelper->injectPageRenderer($pageRenderer);
        $viewHelper->injectEventDispatcher($eventDispatcher);
        $viewHelper->setRenderingContext($renderingContext);

        $viewHelper->setArguments(['enabled' => true, 'pageUid' => 0]);
        $viewHelper->render();
    }
}
