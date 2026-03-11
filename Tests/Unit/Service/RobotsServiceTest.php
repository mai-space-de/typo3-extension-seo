<?php

declare(strict_types = 1);

namespace Maispace\MaispacesSeo\Tests\Unit\Service;

use Maispace\MaispacesSeo\Event\BeforeRobotsRenderedEvent;
use Maispace\MaispacesSeo\Service\RobotsService;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

class RobotsServiceTest extends TestCase
{
    private EventDispatcherInterface $eventDispatcher;
    private RobotsService $subject;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->eventDispatcher
            ->method('dispatch')
            ->willReturnArgument(0);

        $this->subject = new RobotsService($this->eventDispatcher);
    }

    public function testBuildDirectivesReturnsIndexFollowByDefault(): void
    {
        $directives = $this->subject->buildDirectives(
            ['tx_maispace_seo_robots_noindex' => 0, 'tx_maispace_seo_robots_nofollow' => 0, 'tx_maispace_seo_robots_noarchive' => 0],
            []
        );

        self::assertSame('index, follow', $directives);
    }

    public function testBuildDirectivesReturnsNoindexWhenFlagIsSet(): void
    {
        $directives = $this->subject->buildDirectives(
            ['tx_maispace_seo_robots_noindex' => 1, 'tx_maispace_seo_robots_nofollow' => 0],
            []
        );

        self::assertSame('noindex, follow', $directives);
    }

    public function testBuildDirectivesReturnsNofollowWhenFlagIsSet(): void
    {
        $directives = $this->subject->buildDirectives(
            ['tx_maispace_seo_robots_noindex' => 0, 'tx_maispace_seo_robots_nofollow' => 1],
            []
        );

        self::assertSame('index, nofollow', $directives);
    }

    public function testBuildDirectivesAddsNoarchiveWhenFlagIsSet(): void
    {
        $directives = $this->subject->buildDirectives(
            ['tx_maispace_seo_robots_noindex' => 0, 'tx_maispace_seo_robots_nofollow' => 0, 'tx_maispace_seo_robots_noarchive' => 1],
            []
        );

        self::assertStringContainsString('noarchive', $directives);
    }

    public function testBuildDirectivesDoesNotAddNoarchiveWhenFlagIsNotSet(): void
    {
        $directives = $this->subject->buildDirectives(
            ['tx_maispace_seo_robots_noindex' => 0, 'tx_maispace_seo_robots_nofollow' => 0, 'tx_maispace_seo_robots_noarchive' => 0],
            []
        );

        self::assertStringNotContainsString('noarchive', $directives);
    }

    public function testBuildDirectivesReturnsEmptyStringWhenFeatureIsDisabledViaTypoScript(): void
    {
        $directives = $this->subject->buildDirectives(
            ['tx_maispace_seo_robots_noindex' => 0, 'tx_maispace_seo_robots_nofollow' => 0],
            ['robots.' => ['enable' => '0']]
        );

        self::assertSame('', $directives);
    }

    public function testBuildDirectivesReturnsEmptyStringWhenEventDisablesOutput(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(
            static function (BeforeRobotsRenderedEvent $event): BeforeRobotsRenderedEvent {
                $event->disable();

                return $event;
            }
        );

        $subject = new RobotsService($dispatcher);
        $directives = $subject->buildDirectives(
            ['tx_maispace_seo_robots_noindex' => 0, 'tx_maispace_seo_robots_nofollow' => 0],
            []
        );

        self::assertSame('', $directives);
    }

    public function testBuildDirectivesAllowsListenerToModifyDirectives(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(
            static function (BeforeRobotsRenderedEvent $event): BeforeRobotsRenderedEvent {
                $event->setDirectives('noindex, nofollow, noarchive');

                return $event;
            }
        );

        $subject = new RobotsService($dispatcher);
        $directives = $subject->buildDirectives(
            ['tx_maispace_seo_robots_noindex' => 0, 'tx_maispace_seo_robots_nofollow' => 0],
            []
        );

        self::assertSame('noindex, nofollow, noarchive', $directives);
    }

    public function testRenderTagReturnsMetaRobotsTag(): void
    {
        $tag = $this->subject->renderTag('index, follow');

        self::assertSame('<meta name="robots" content="index, follow">', $tag);
    }

    public function testRenderTagEscapesSpecialCharactersInDirectives(): void
    {
        $tag = $this->subject->renderTag('index, follow & more');

        self::assertStringContainsString('content="index, follow &amp; more"', $tag);
    }

    public function testRenderTagReturnsEmptyStringForEmptyDirectives(): void
    {
        self::assertSame('', $this->subject->renderTag(''));
    }
}
