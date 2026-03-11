<?php

declare(strict_types = 1);

namespace Maispace\MaispacesSeo\Tests\Unit\Service;

use Maispace\MaispacesSeo\Event\BeforeOpenGraphRenderedEvent;
use Maispace\MaispacesSeo\Service\OpenGraphService;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

class OpenGraphServiceTest extends TestCase
{
    private EventDispatcherInterface $eventDispatcher;
    private OpenGraphService $subject;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->eventDispatcher
            ->method('dispatch')
            ->willReturnArgument(0);

        $this->subject = new OpenGraphService($this->eventDispatcher);
    }

    private function findProperty(array $properties, string $name): ?string
    {
        foreach ($properties as $prop) {
            if ($prop['property'] === $name) {
                return $prop['content'];
            }
        }

        return null;
    }

    public function testBuildPropertiesUsesPageTitleAsFallbackForOgTitle(): void
    {
        $properties = $this->subject->buildProperties(
            ['title' => 'My Page', 'tx_maispace_seo_og_title' => ''],
            []
        );

        self::assertSame('My Page', $this->findProperty($properties, 'og:title'));
    }

    public function testBuildPropertiesUsesOgTitleOverrideWhenSet(): void
    {
        $properties = $this->subject->buildProperties(
            ['title' => 'My Page', 'tx_maispace_seo_og_title' => 'Custom OG Title'],
            []
        );

        self::assertSame('Custom OG Title', $this->findProperty($properties, 'og:title'));
    }

    public function testBuildPropertiesDefaultsOgTypeToWebsite(): void
    {
        $properties = $this->subject->buildProperties(
            ['title' => 'My Page', 'tx_maispace_seo_og_type' => ''],
            []
        );

        self::assertSame('website', $this->findProperty($properties, 'og:type'));
    }

    public function testBuildPropertiesUsesOgTypeOverride(): void
    {
        $properties = $this->subject->buildProperties(
            ['title' => 'My Page', 'tx_maispace_seo_og_type' => 'article'],
            []
        );

        self::assertSame('article', $this->findProperty($properties, 'og:type'));
    }

    public function testBuildPropertiesIncludesTwitterPropertiesWhenEnabled(): void
    {
        $properties = $this->subject->buildProperties(
            ['title' => 'My Page', 'tx_maispace_seo_og_title' => '', 'tx_maispace_seo_og_type' => '', 'tx_maispace_seo_twitter_card' => 'summary'],
            ['openGraph.' => ['twitter' => '1']]
        );

        self::assertSame('summary', $this->findProperty($properties, 'twitter:card'));
    }

    public function testBuildPropertiesOmitsTwitterPropertiesWhenDisabled(): void
    {
        $properties = $this->subject->buildProperties(
            ['title' => 'My Page'],
            ['openGraph.' => ['twitter' => '0']]
        );

        $twitterProperties = array_filter(
            $properties,
            static fn (array $p): bool => str_starts_with($p['property'], 'twitter:')
        );

        self::assertCount(0, $twitterProperties);
    }

    public function testBuildPropertiesIncludesOgSiteNameFromSettings(): void
    {
        $properties = $this->subject->buildProperties(
            ['title' => 'My Page'],
            ['openGraph.' => ['siteName' => 'My Site']]
        );

        self::assertSame('My Site', $this->findProperty($properties, 'og:site_name'));
    }

    public function testBuildPropertiesOmitsOgSiteNameWhenNotSet(): void
    {
        $properties = $this->subject->buildProperties(
            ['title' => 'My Page'],
            []
        );

        self::assertNull($this->findProperty($properties, 'og:site_name'));
    }

    public function testBuildPropertiesOmitsOgImageWhenNoUrlPassed(): void
    {
        $properties = $this->subject->buildProperties(
            ['title' => 'My Page'],
            [],
            ''
        );

        self::assertNull($this->findProperty($properties, 'og:image'));
    }

    public function testBuildPropertiesIncludesOgImageWhenUrlPassed(): void
    {
        $properties = $this->subject->buildProperties(
            ['title' => 'My Page'],
            [],
            'https://example.com/image.jpg'
        );

        self::assertSame('https://example.com/image.jpg', $this->findProperty($properties, 'og:image'));
    }

    public function testBuildPropertiesReturnsEmptyArrayWhenEventDisablesOutput(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(
            static function (BeforeOpenGraphRenderedEvent $event): BeforeOpenGraphRenderedEvent {
                $event->disable();

                return $event;
            }
        );

        $subject = new OpenGraphService($dispatcher);
        $properties = $subject->buildProperties(
            ['title' => 'My Page'],
            []
        );

        self::assertSame([], $properties);
    }

    public function testBuildPropertiesOmitsOgUrlWhenNotPassed(): void
    {
        $properties = $this->subject->buildProperties(
            ['title' => 'My Page'],
            []
        );

        self::assertNull($this->findProperty($properties, 'og:url'));
    }

    public function testBuildPropertiesIncludesOgUrlWhenPassed(): void
    {
        $properties = $this->subject->buildProperties(
            ['title' => 'My Page'],
            [],
            '',
            '',
            'https://example.com/my-page'
        );

        self::assertSame('https://example.com/my-page', $this->findProperty($properties, 'og:url'));
    }
}
