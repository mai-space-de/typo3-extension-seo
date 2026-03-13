<?php

declare(strict_types = 1);

namespace Maispace\MaispacesSeo\Tests\Unit\ViewHelper;

use Maispace\MaispacesSeo\Event\AfterMetaDescriptionRenderedEvent;
use Maispace\MaispacesSeo\Service\MetaDescriptionService;
use Maispace\MaispacesSeo\ViewHelpers\Seo\MetaDescriptionViewHelper;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

class MetaDescriptionViewHelperTest extends TestCase
{
    public function testRenderReturnsEmptyStringWhenEnabledIsFalse(): void
    {
        $viewHelper = new MetaDescriptionViewHelper();

        $service = $this->createMock(MetaDescriptionService::class);
        $service->expects(self::never())->method('buildDescription');

        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer->expects(self::never())->method('setMetaTag');

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $viewHelper->injectMetaDescriptionService($service);
        $viewHelper->injectPageRenderer($pageRenderer);
        $viewHelper->injectEventDispatcher($eventDispatcher);

        $viewHelper->setArguments(['enabled' => false, 'pageUid' => 0]);

        $result = $viewHelper->render();

        self::assertSame('', $result);
    }

    public function testRenderCallsServiceAndAddsToPageRenderer(): void
    {
        $pageRecord = ['uid' => 1, 'title' => 'Test Page', 'tx_maispace_seo_meta_description' => 'A test description.'];

        $service = $this->createMock(MetaDescriptionService::class);
        $service->method('buildDescription')->willReturn('A test description.');

        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer->expects(self::once())->method('setMetaTag')->with('name', 'description', 'A test description.');

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

        $viewHelper = new MetaDescriptionViewHelper();
        $viewHelper->injectMetaDescriptionService($service);
        $viewHelper->injectPageRenderer($pageRenderer);
        $viewHelper->injectEventDispatcher($eventDispatcher);
        $viewHelper->setRenderingContext($renderingContext);

        $viewHelper->setArguments(['enabled' => true, 'pageUid' => 0]);

        $result = $viewHelper->render();

        self::assertSame('', $result);
    }

    public function testRenderDoesNotCallPageRendererWhenDescriptionIsEmpty(): void
    {
        $pageRecord = ['uid' => 1, 'title' => 'Test Page', 'tx_maispace_seo_meta_description' => ''];

        $service = $this->createMock(MetaDescriptionService::class);
        $service->method('buildDescription')->willReturn('');

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

        $viewHelper = new MetaDescriptionViewHelper();
        $viewHelper->injectMetaDescriptionService($service);
        $viewHelper->injectPageRenderer($pageRenderer);
        $viewHelper->injectEventDispatcher($eventDispatcher);
        $viewHelper->setRenderingContext($renderingContext);

        $viewHelper->setArguments(['enabled' => true, 'pageUid' => 0]);

        $result = $viewHelper->render();

        self::assertSame('', $result);
    }

    public function testRenderUsesModifiedDescriptionFromAfterEvent(): void
    {
        $pageRecord = ['uid' => 1, 'title' => 'Test Page', 'tx_maispace_seo_meta_description' => 'Original description.'];

        $service = $this->createMock(MetaDescriptionService::class);
        $service->method('buildDescription')->willReturn('Original description.');

        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer->expects(self::once())->method('setMetaTag')->with('name', 'description', 'Modified description.');

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnCallback(
            static function (AfterMetaDescriptionRenderedEvent $event): AfterMetaDescriptionRenderedEvent {
                $event->setDescription('Modified description.');

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

        $viewHelper = new MetaDescriptionViewHelper();
        $viewHelper->injectMetaDescriptionService($service);
        $viewHelper->injectPageRenderer($pageRenderer);
        $viewHelper->injectEventDispatcher($eventDispatcher);
        $viewHelper->setRenderingContext($renderingContext);

        $viewHelper->setArguments(['enabled' => true, 'pageUid' => 0]);

        $result = $viewHelper->render();

        self::assertSame('', $result);
    }
}
