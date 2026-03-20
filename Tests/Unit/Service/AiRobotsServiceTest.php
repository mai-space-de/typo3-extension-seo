<?php

declare(strict_types = 1);

namespace Maispace\MaiSeo\Tests\Unit\Service;

use Maispace\MaiSeo\Event\BeforeAiRobotsRenderedEvent;
use Maispace\MaiSeo\Service\AiRobotsService;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

class AiRobotsServiceTest extends TestCase
{
    private EventDispatcherInterface $eventDispatcher;
    private AiRobotsService $subject;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->eventDispatcher
            ->method('dispatch')
            ->willReturnArgument(0);

        $this->subject = new AiRobotsService($this->eventDispatcher);
    }

    public function testBuildTagsReturnsEmptyArrayWhenNoindexIsNotSet(): void
    {
        $tags = $this->subject->buildTags(
            ['tx_maiseo_ai_noindex' => 0],
            []
        );

        self::assertSame([], $tags);
    }

    public function testBuildTagsReturnsPerBotNoindexTagsWhenNoindexIsSet(): void
    {
        $tags = $this->subject->buildTags(
            ['tx_maiseo_ai_noindex' => 1],
            []
        );

        self::assertNotEmpty($tags);
        foreach ($tags as $tag) {
            self::assertSame('noindex', $tag['content']);
            self::assertNotEmpty($tag['name']);
        }
    }

    public function testBuildTagsUsesDefaultBotsWhenNotConfigured(): void
    {
        $tags = $this->subject->buildTags(
            ['tx_maiseo_ai_noindex' => 1],
            []
        );

        $names = array_column($tags, 'name');
        self::assertContains('GPTBot', $names);
        self::assertContains('ClaudeBot', $names);
        self::assertContains('Google-Extended', $names);
    }

    public function testBuildTagsUsesConfiguredBotsFromTypoScript(): void
    {
        $tags = $this->subject->buildTags(
            ['tx_maiseo_ai_noindex' => 1],
            ['aiRobots.' => ['bots' => 'GPTBot, MyCustomBot']]
        );

        $names = array_column($tags, 'name');
        self::assertSame(['GPTBot', 'MyCustomBot'], $names);
    }

    public function testBuildTagsReturnsEmptyArrayWhenFeatureIsDisabledViaTypoScript(): void
    {
        $tags = $this->subject->buildTags(
            ['tx_maiseo_ai_noindex' => 1],
            ['aiRobots.' => ['enable' => '0']]
        );

        self::assertSame([], $tags);
    }

    public function testBuildTagsReturnsEmptyArrayWhenEventDisablesOutput(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(
            static function (BeforeAiRobotsRenderedEvent $event): BeforeAiRobotsRenderedEvent {
                $event->disable();

                return $event;
            }
        );

        $subject = new AiRobotsService($dispatcher);
        $tags = $subject->buildTags(
            ['tx_maiseo_ai_noindex' => 1],
            []
        );

        self::assertSame([], $tags);
    }

    public function testBuildTagsAllowsListenerToModifyTags(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(
            static function (BeforeAiRobotsRenderedEvent $event): BeforeAiRobotsRenderedEvent {
                $event->setTags([['name' => 'CustomBot', 'content' => 'noindex']]);

                return $event;
            }
        );

        $subject = new AiRobotsService($dispatcher);
        $tags = $subject->buildTags(
            ['tx_maiseo_ai_noindex' => 1],
            []
        );

        self::assertCount(1, $tags);
        self::assertSame('CustomBot', $tags[0]['name']);
    }

    public function testBuildTagsFiltersEmptyBotNamesFromConfig(): void
    {
        $tags = $this->subject->buildTags(
            ['tx_maiseo_ai_noindex' => 1],
            ['aiRobots.' => ['bots' => 'GPTBot, , ClaudeBot']]
        );

        $names = array_column($tags, 'name');
        self::assertSame(['GPTBot', 'ClaudeBot'], $names);
    }
}
