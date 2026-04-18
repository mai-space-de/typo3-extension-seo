<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Event;

final class StructuredDataCollectionEvent
{
    private array $graph = [];

    public function __construct(
        public readonly int $pageUid,
        public readonly array $pageRecord,
    ) {}

    public function addToGraph(string $property, mixed $value): void
    {
        $this->graph[$property] = $value;
    }

    public function mergeIntoGraph(array $data): void
    {
        $this->graph = array_merge_recursive($this->graph, $data);
    }

    public function getGraph(): array
    {
        return $this->graph;
    }

    public function setRootType(string $type): void
    {
        $this->graph['@type'] = $type;
    }
}
