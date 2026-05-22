<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\Event;

use Maispace\MaiSeo\Event\VocabularyLoadedEvent;
use Maispace\MaiSeo\Schema\VocabularyRegistryInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class VocabularyLoadedEventTest extends TestCase
{
    #[Test]
    public function getRegistryReturnsInjectedRegistry(): void
    {
        $registry = $this->createMock(VocabularyRegistryInterface::class);
        $event = new VocabularyLoadedEvent($registry);

        self::assertSame($registry, $event->getRegistry());
    }

    #[Test]
    public function eventCarriesRegistryForListenerMutations(): void
    {
        $registry = $this->createMock(VocabularyRegistryInterface::class);
        $registry->expects(self::once())
            ->method('registerType');

        $event = new VocabularyLoadedEvent($registry);

        // Simulate what a PSR-14 listener would do: extend the registry
        $event->getRegistry()->registerType(
            new \Maispace\MaiSeo\Schema\TypeDefinition(
                name: 'CustomType',
                label: 'Custom Type',
                ancestors: ['Thing'],
                properties: ['name'],
            ),
        );
    }
}
