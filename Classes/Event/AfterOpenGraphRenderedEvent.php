<?php

declare(strict_types=1);

namespace Maispace\MaispacesSeo\Event;

final class AfterOpenGraphRenderedEvent
{
    /**
     * @param list<array{property: string, content: string}> $properties
     */
    public function __construct(private readonly array $properties) {}

    /**
     * @return list<array{property: string, content: string}>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }
}
