<?php

declare(strict_types = 1);

namespace Maispace\MaiSeo\Tests\Unit\Service;

use Maispace\MaiSeo\Event\BeforeCanonicalRenderedEvent;
use Maispace\MaiSeo\Service\CanonicalService;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

class CanonicalServiceTest extends TestCase
{
    private EventDispatcherInterface $eventDispatcher;
    private CanonicalService $subject;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->eventDispatcher
            ->method('dispatch')
            ->willReturnArgument(0);

        $this->subject = new CanonicalService($this->eventDispatcher);
    }

    public function testBuildCanonicalUrlReturnsCustomOverrideWhenSet(): void
    {
        $url = $this->subject->buildCanonicalUrl(
            ['tx_maiseo_canonical_url' => 'https://example.com/custom', 'canonical_link' => 'https://example.com/core'],
            []
        );

        self::assertSame('https://example.com/custom', $url);
    }

    public function testBuildCanonicalUrlFallsBackToCoreCanonicalLink(): void
    {
        $url = $this->subject->buildCanonicalUrl(
            ['tx_maiseo_canonical_url' => '', 'canonical_link' => 'https://example.com/core'],
            []
        );

        self::assertSame('https://example.com/core', $url);
    }

    public function testBuildCanonicalUrlReturnsEmptyStringWhenBothFieldsAreEmpty(): void
    {
        $url = $this->subject->buildCanonicalUrl(
            ['tx_maiseo_canonical_url' => '', 'canonical_link' => ''],
            []
        );

        self::assertSame('', $url);
    }

    public function testBuildCanonicalUrlReturnsEmptyStringWhenFeatureIsDisabledViaTypoScript(): void
    {
        $url = $this->subject->buildCanonicalUrl(
            ['tx_maiseo_canonical_url' => 'https://example.com/custom'],
            ['canonical.' => ['enable' => '0']]
        );

        self::assertSame('', $url);
    }

    public function testBuildCanonicalUrlReturnsEmptyStringWhenEventDisablesOutput(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(
            static function (BeforeCanonicalRenderedEvent $event): BeforeCanonicalRenderedEvent {
                $event->disable();

                return $event;
            }
        );

        $subject = new CanonicalService($dispatcher);
        $url = $subject->buildCanonicalUrl(
            ['tx_maiseo_canonical_url' => 'https://example.com/custom'],
            []
        );

        self::assertSame('', $url);
    }

    public function testBuildCanonicalUrlAllowsListenerToModifyUrl(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(
            static function (BeforeCanonicalRenderedEvent $event): BeforeCanonicalRenderedEvent {
                $event->setCanonicalUrl('https://example.com/modified');

                return $event;
            }
        );

        $subject = new CanonicalService($dispatcher);
        $url = $subject->buildCanonicalUrl(
            ['tx_maiseo_canonical_url' => 'https://example.com/original'],
            []
        );

        self::assertSame('https://example.com/modified', $url);
    }

    public function testRenderTagReturnsLinkElementWithHref(): void
    {
        $tag = $this->subject->renderTag('https://example.com/page');

        self::assertSame('<link rel="canonical" href="https://example.com/page">', $tag);
    }

    public function testRenderTagEscapesSpecialCharactersInUrl(): void
    {
        $tag = $this->subject->renderTag('https://example.com/page?a=1&b=2');

        self::assertStringContainsString('href="https://example.com/page?a=1&amp;b=2"', $tag);
    }

    public function testRenderTagReturnsEmptyStringForEmptyUrl(): void
    {
        self::assertSame('', $this->subject->renderTag(''));
    }
}
