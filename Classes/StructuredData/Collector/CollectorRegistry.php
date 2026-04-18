<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\StructuredData\Collector;

final class CollectorRegistry
{
    /** @var CollectorInterface[] */
    private array $collectors = [];

    public function addCollector(CollectorInterface $collector): void
    {
        $this->collectors[] = $collector;
    }

    public function getCollectors(): array
    {
        $collectors = $this->collectors;
        usort($collectors, static fn(CollectorInterface $a, CollectorInterface $b): int => $b->priority() <=> $a->priority());
        return $collectors;
    }
}
