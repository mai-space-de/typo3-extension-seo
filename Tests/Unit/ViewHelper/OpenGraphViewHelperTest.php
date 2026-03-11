<?php

declare(strict_types = 1);

namespace Maispace\MaispacesSeo\Tests\Unit\ViewHelper;

use Maispace\MaispacesSeo\Service\OpenGraphService;
use Maispace\MaispacesSeo\ViewHelpers\Seo\OpenGraphViewHelper;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

class OpenGraphViewHelperTest extends TestCase
{
    public function testRenderReturnsEmptyStringWhenEnabledIsFalse(): void
    {
        $openGraphService = $this->createMock(OpenGraphService::class);
        $openGraphService->expects(self::never())->method('buildProperties');

        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer->expects(self::never())->method('setMetaTag');

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $viewHelper = new OpenGraphViewHelper();
        $viewHelper->injectOpenGraphService($openGraphService);
        $viewHelper->injectPageRenderer($pageRenderer);
        $viewHelper->injectEventDispatcher($eventDispatcher);

        $viewHelper->setArguments(['enabled' => false, 'pageUid' => 0, 'twitter' => true]);

        $result = $viewHelper->render();

        self::assertSame('', $result);
    }

    public function testRenderCallsServiceAndSetsMetaTags(): void
    {
        $pageRecord = ['uid' => 1, 'title' => 'Test Page'];
        $properties = [
            ['property' => 'og:type', 'content' => 'website'],
            ['property' => 'og:title', 'content' => 'Test Page'],
        ];

        $openGraphService = $this->createMock(OpenGraphService::class);
        $openGraphService->method('buildProperties')->willReturn($properties);

        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer->expects(self::exactly(2))->method('setMetaTag');

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

        $viewHelper = new OpenGraphViewHelper();
        $viewHelper->injectOpenGraphService($openGraphService);
        $viewHelper->injectPageRenderer($pageRenderer);
        $viewHelper->injectEventDispatcher($eventDispatcher);
        $viewHelper->setRenderingContext($renderingContext);

        $viewHelper->setArguments(['enabled' => true, 'pageUid' => 0, 'twitter' => true]);

        $result = $viewHelper->render();

        self::assertSame('', $result);
    }

    public function testRenderFiltersTwitterPropertiesWhenTwitterIsFalse(): void
    {
        $pageRecord = ['uid' => 1, 'title' => 'Test Page'];
        $properties = [
            ['property' => 'og:type', 'content' => 'website'],
            ['property' => 'og:title', 'content' => 'Test Page'],
            ['property' => 'twitter:card', 'content' => 'summary'],
            ['property' => 'twitter:title', 'content' => 'Test Page'],
        ];

        $openGraphService = $this->createMock(OpenGraphService::class);
        $openGraphService->method('buildProperties')->willReturn($properties);

        // Only 2 og: properties should be set (twitter: ones are filtered out)
        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer->expects(self::exactly(2))->method('setMetaTag');

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

        $viewHelper = new OpenGraphViewHelper();
        $viewHelper->injectOpenGraphService($openGraphService);
        $viewHelper->injectPageRenderer($pageRenderer);
        $viewHelper->injectEventDispatcher($eventDispatcher);
        $viewHelper->setRenderingContext($renderingContext);

        $viewHelper->setArguments(['enabled' => true, 'pageUid' => 0, 'twitter' => false]);

        $viewHelper->render();
    }
}
