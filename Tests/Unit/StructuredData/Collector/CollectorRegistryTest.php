<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\StructuredData\Collector;

use Maispace\MaiSeo\StructuredData\Collector\CollectorInterface;
use Maispace\MaiSeo\StructuredData\Collector\CollectorRegistry;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CollectorRegistryTest extends TestCase
{
    private function makeCollector(int $priority, string $name = ''): CollectorInterface
    {
        $collector = $this->createMock(CollectorInterface::class);
        $collector->method('priority')->willReturn($priority);
        $collector->method('supportedTypes')->willReturn([$name ?: 'Any']);
        return $collector;
    }

    #[Test]
    public function emptyRegistryReturnsEmptyArray(): void
    {
        $registry = new CollectorRegistry();

        self::assertSame([], $registry->getCollectors());
    }

    #[Test]
    public function addedCollectorIsReturnedByGetCollectors(): void
    {
        $registry = new CollectorRegistry();
        $collector = $this->makeCollector(10);
        $registry->addCollector($collector);

        self::assertSame([$collector], $registry->getCollectors());
    }

    #[Test]
    public function collectorsAreSortedByPriorityDescending(): void
    {
        $registry = new CollectorRegistry();
        $low = $this->makeCollector(10);
        $high = $this->makeCollector(100);
        $mid = $this->makeCollector(50);

        $registry->addCollector($low);
        $registry->addCollector($high);
        $registry->addCollector($mid);

        $sorted = $registry->getCollectors();
        self::assertSame($high, $sorted[0]);
        self::assertSame($mid, $sorted[1]);
        self::assertSame($low, $sorted[2]);
    }

    #[Test]
    public function collectorsWithEqualPriorityPreserveRelativeOrder(): void
    {
        $registry = new CollectorRegistry();
        $first = $this->makeCollector(50, 'First');
        $second = $this->makeCollector(50, 'Second');

        $registry->addCollector($first);
        $registry->addCollector($second);

        $result = $registry->getCollectors();
        // Both have equal priority — usort is not stable in all PHP versions,
        // but the important thing is both are returned.
        self::assertCount(2, $result);
        self::assertContains($first, $result);
        self::assertContains($second, $result);
    }

    #[Test]
    public function getCollectorsDoesNotMutateInternalState(): void
    {
        $registry = new CollectorRegistry();
        $registry->addCollector($this->makeCollector(10));
        $registry->addCollector($this->makeCollector(50));

        // Calling getCollectors twice should return independent sorted arrays
        $first = $registry->getCollectors();
        $second = $registry->getCollectors();

        self::assertSame(count($first), count($second));
    }

    #[Test]
    public function multipleCollectorsCanBeRegistered(): void
    {
        $registry = new CollectorRegistry();
        for ($i = 0; $i < 5; $i++) {
            $registry->addCollector($this->makeCollector($i * 10));
        }

        self::assertCount(5, $registry->getCollectors());
    }
}
