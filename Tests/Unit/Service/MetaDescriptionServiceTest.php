<?php

declare(strict_types = 1);

namespace Maispace\MaiSeo\Tests\Unit\Service;

use Maispace\MaiSeo\Event\BeforeMetaDescriptionRenderedEvent;
use Maispace\MaiSeo\Service\MetaDescriptionService;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

class MetaDescriptionServiceTest extends TestCase
{
    private EventDispatcherInterface $eventDispatcher;
    private MetaDescriptionService $subject;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->eventDispatcher
            ->method('dispatch')
            ->willReturnArgument(0);

        $this->subject = new MetaDescriptionService($this->eventDispatcher);
    }

    public function testBuildDescriptionUsesCustomOverrideWhenSet(): void
    {
        $description = $this->subject->buildDescription(
            [
                'tx_maiseo_meta_description'       => 'Custom meta description',
                'description'                      => 'Core description',
                'abstract'                         => 'Page abstract',
            ],
            []
        );

        self::assertSame('Custom meta description', $description);
    }

    public function testBuildDescriptionFallsBackToCoreDescriptionField(): void
    {
        $description = $this->subject->buildDescription(
            [
                'tx_maiseo_meta_description'       => '',
                'description'                      => 'Core description',
                'abstract'                         => 'Page abstract',
            ],
            []
        );

        self::assertSame('Core description', $description);
    }

    public function testBuildDescriptionFallsBackToAbstractWhenDescriptionIsEmpty(): void
    {
        $description = $this->subject->buildDescription(
            [
                'tx_maiseo_meta_description'       => '',
                'description'                      => '',
                'abstract'                         => 'Page abstract',
            ],
            []
        );

        self::assertSame('Page abstract', $description);
    }

    public function testBuildDescriptionReturnsEmptyStringWhenAllFieldsAreEmpty(): void
    {
        $description = $this->subject->buildDescription(
            [
                'tx_maiseo_meta_description'       => '',
                'description'                      => '',
                'abstract'                         => '',
            ],
            []
        );

        self::assertSame('', $description);
    }

    public function testBuildDescriptionReturnsEmptyStringWhenFeatureIsDisabledViaTypoScript(): void
    {
        $description = $this->subject->buildDescription(
            ['tx_maiseo_meta_description' => 'Custom meta description'],
            ['metaDescription.' => ['enable' => '0']]
        );

        self::assertSame('', $description);
    }

    public function testBuildDescriptionReturnsEmptyStringWhenEventDisablesOutput(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(
            static function (BeforeMetaDescriptionRenderedEvent $event): BeforeMetaDescriptionRenderedEvent {
                $event->disable();

                return $event;
            }
        );

        $subject = new MetaDescriptionService($dispatcher);
        $description = $subject->buildDescription(
            ['tx_maiseo_meta_description' => 'Custom meta description'],
            []
        );

        self::assertSame('', $description);
    }

    public function testBuildDescriptionAllowsListenerToModifyDescription(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(
            static function (BeforeMetaDescriptionRenderedEvent $event): BeforeMetaDescriptionRenderedEvent {
                $event->setDescription('Modified description');

                return $event;
            }
        );

        $subject = new MetaDescriptionService($dispatcher);
        $description = $subject->buildDescription(
            ['tx_maiseo_meta_description' => 'Original description'],
            []
        );

        self::assertSame('Modified description', $description);
    }

    public function testRenderTagReturnsMetaDescriptionTag(): void
    {
        $tag = $this->subject->renderTag('A short page description.');

        self::assertSame('<meta name="description" content="A short page description.">', $tag);
    }

    public function testRenderTagEscapesSpecialCharactersInDescription(): void
    {
        $tag = $this->subject->renderTag('Prices start at 5 € & more');

        self::assertStringContainsString('content="Prices start at 5 € &amp; more"', $tag);
    }

    public function testRenderTagReturnsEmptyStringForEmptyDescription(): void
    {
        self::assertSame('', $this->subject->renderTag(''));
    }
}
