<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Event;

final class StructuredDataRenderEvent
{
    public function __construct(
        public readonly int $pageUid,
        private array $graph,
    ) {}

    public function getGraph(): array
    {
        return $this->graph;
    }

    public function setGraph(array $graph): void
    {
        $this->graph = $graph;
    }

    public function removeProperty(string $property): void
    {
        unset($this->graph[$property]);
    }
}
