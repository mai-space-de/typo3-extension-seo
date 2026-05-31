<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\StructuredData\Collector;

final class CollectorRegistry
{
    /** @var list<CollectorInterface> */
    private array $collectors;

    /** @param iterable<CollectorInterface> $collectors */
    public function __construct(
        iterable $collectors = [],
    ) {
        $this->collectors = [];
        foreach ($collectors as $collector) {
            $this->collectors[] = $collector;
        }
    }

    public function addCollector(CollectorInterface $collector): void
    {
        $this->collectors[] = $collector;
    }

    /** @return list<CollectorInterface> */
    public function getCollectors(): array
    {
        $collectors = $this->collectors;
        usort($collectors, static fn(CollectorInterface $a, CollectorInterface $b): int => $b->priority() <=> $a->priority());
        return $collectors;
    }
}
