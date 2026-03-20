<?php

declare(strict_types = 1);

namespace Maispace\MaiSeo\Tests\Unit\ViewHelper;

use Maispace\MaiSeo\Event\AfterAiRobotsRenderedEvent;
use Maispace\MaiSeo\Service\AiRobotsService;
use Maispace\MaiSeo\ViewHelpers\Seo\AiRobotsViewHelper;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

class AiRobotsViewHelperTest extends TestCase
{
    public function testRenderReturnsEmptyStringWhenEnabledIsFalse(): void
    {
        $viewHelper = new AiRobotsViewHelper();

        $service = $this->createMock(AiRobotsService::class);
        $service->expects(self::never())->method('buildTags');

        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer->expects(self::never())->method('setMetaTag');

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $viewHelper->injectAiRobotsService($service);
        $viewHelper->injectPageRenderer($pageRenderer);
        $viewHelper->injectEventDispatcher($eventDispatcher);

        $viewHelper->setArguments(['enabled' => false, 'pageUid' => 0]);

        $result = $viewHelper->render();

        self::assertSame('', $result);
    }

    public function testRenderCallsServiceAndAddsPerBotTagsToPageRenderer(): void
    {
        $pageRecord = ['uid' => 1, 'title' => 'Test Page', 'tx_maiseo_ai_noindex' => 1];
        $tags = [
            ['name' => 'GPTBot', 'content' => 'noindex'],
            ['name' => 'ClaudeBot', 'content' => 'noindex'],
        ];

        $service = $this->createMock(AiRobotsService::class);
        $service->method('buildTags')->willReturn($tags);

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

        $viewHelper = new AiRobotsViewHelper();
        $viewHelper->injectAiRobotsService($service);
        $viewHelper->injectPageRenderer($pageRenderer);
        $viewHelper->injectEventDispatcher($eventDispatcher);
        $viewHelper->setRenderingContext($renderingContext);

        $viewHelper->setArguments(['enabled' => true, 'pageUid' => 0]);

        $result = $viewHelper->render();

        self::assertSame('', $result);
    }

    public function testRenderDoesNotCallPageRendererWhenTagsAreEmpty(): void
    {
        $pageRecord = ['uid' => 1, 'title' => 'Test Page', 'tx_maiseo_ai_noindex' => 0];

        $service = $this->createMock(AiRobotsService::class);
        $service->method('buildTags')->willReturn([]);

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

        $viewHelper = new AiRobotsViewHelper();
        $viewHelper->injectAiRobotsService($service);
        $viewHelper->injectPageRenderer($pageRenderer);
        $viewHelper->injectEventDispatcher($eventDispatcher);
        $viewHelper->setRenderingContext($renderingContext);

        $viewHelper->setArguments(['enabled' => true, 'pageUid' => 0]);

        $result = $viewHelper->render();

        self::assertSame('', $result);
    }

    public function testRenderSkipsTagsWithEmptyNameAddedByListener(): void
    {
        $pageRecord = ['uid' => 1, 'title' => 'Test Page', 'tx_maiseo_ai_noindex' => 1];

        $service = $this->createMock(AiRobotsService::class);
        $service->method('buildTags')->willReturn([['name' => 'GPTBot', 'content' => 'noindex']]);

        // Listener injects a tag with an empty name — must not reach PageRenderer
        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer->expects(self::once())->method('setMetaTag')->with('name', 'GPTBot', 'noindex');

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnCallback(
            static function (AfterAiRobotsRenderedEvent $event): AfterAiRobotsRenderedEvent {
                $event->setTags([
                    ['name' => 'GPTBot', 'content' => 'noindex'],
                    ['name' => '', 'content' => 'noindex'],
                ]);

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

        $viewHelper = new AiRobotsViewHelper();
        $viewHelper->injectAiRobotsService($service);
        $viewHelper->injectPageRenderer($pageRenderer);
        $viewHelper->injectEventDispatcher($eventDispatcher);
        $viewHelper->setRenderingContext($renderingContext);

        $viewHelper->setArguments(['enabled' => true, 'pageUid' => 0]);

        $result = $viewHelper->render();

        self::assertSame('', $result);
    }
}
