<?php

declare(strict_types = 1);

namespace Maispace\MaiSeo\Tests\Unit\Service;

use Maispace\MaiSeo\Event\BeforeLlmsTxtRenderedEvent;
use Maispace\MaiSeo\Service\LlmsTxtService;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

class LlmsTxtServiceTest extends TestCase
{
    private EventDispatcherInterface $eventDispatcher;
    private LlmsTxtService $subject;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->eventDispatcher
            ->method('dispatch')
            ->willReturnArgument(0);

        $this->subject = new LlmsTxtService($this->eventDispatcher);
    }

    public function testBuildUrlReturnsDefaultUrlWhenNotConfigured(): void
    {
        $url = $this->subject->buildUrl(
            ['uid' => 1, 'title' => 'Test'],
            []
        );

        self::assertSame('/llms.txt', $url);
    }

    public function testBuildUrlReturnsConfiguredUrl(): void
    {
        $url = $this->subject->buildUrl(
            ['uid' => 1, 'title' => 'Test'],
            ['llmsTxt.' => ['url' => '/custom-llms.txt']]
        );

        self::assertSame('/custom-llms.txt', $url);
    }

    public function testBuildUrlReturnsAbsoluteConfiguredUrl(): void
    {
        $url = $this->subject->buildUrl(
            ['uid' => 1, 'title' => 'Test'],
            ['llmsTxt.' => ['url' => 'https://example.com/llms.txt']]
        );

        self::assertSame('https://example.com/llms.txt', $url);
    }

    public function testBuildUrlReturnsEmptyStringWhenFeatureIsDisabledViaTypoScript(): void
    {
        $url = $this->subject->buildUrl(
            ['uid' => 1, 'title' => 'Test'],
            ['llmsTxt.' => ['enable' => '0']]
        );

        self::assertSame('', $url);
    }

    public function testBuildUrlReturnsEmptyStringWhenEventDisablesOutput(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(
            static function (BeforeLlmsTxtRenderedEvent $event): BeforeLlmsTxtRenderedEvent {
                $event->disable();

                return $event;
            }
        );

        $subject = new LlmsTxtService($dispatcher);
        $url = $subject->buildUrl(
            ['uid' => 1, 'title' => 'Test'],
            []
        );

        self::assertSame('', $url);
    }

    public function testBuildUrlAllowsListenerToModifyUrl(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(
            static function (BeforeLlmsTxtRenderedEvent $event): BeforeLlmsTxtRenderedEvent {
                $event->setUrl('https://example.com/modified-llms.txt');

                return $event;
            }
        );

        $subject = new LlmsTxtService($dispatcher);
        $url = $subject->buildUrl(
            ['uid' => 1, 'title' => 'Test'],
            []
        );

        self::assertSame('https://example.com/modified-llms.txt', $url);
    }

    public function testRenderTagReturnsLinkElementWithHref(): void
    {
        $tag = $this->subject->renderTag('/llms.txt');

        self::assertSame('<link rel="llms-txt" href="/llms.txt" type="text/plain">', $tag);
    }

    public function testRenderTagEscapesSpecialCharactersInUrl(): void
    {
        $tag = $this->subject->renderTag('/llms.txt?a=1&b=2');

        self::assertStringContainsString('href="/llms.txt?a=1&amp;b=2"', $tag);
    }

    public function testRenderTagReturnsEmptyStringForEmptyUrl(): void
    {
        self::assertSame('', $this->subject->renderTag(''));
    }

    public function testBuildUrlFallsBackToDefaultWhenConfiguredUrlIsEmpty(): void
    {
        $url = $this->subject->buildUrl(
            ['uid' => 1, 'title' => 'Test'],
            ['llmsTxt.' => ['url' => '']]
        );

        self::assertSame('/llms.txt', $url);
    }
}
