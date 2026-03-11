<?php

declare(strict_types=1);

namespace Maispace\MaispacesSeo\Event;

final class AfterOpenGraphRenderedEvent
{
    public function __construct(private readonly array $properties) {}

    public function getProperties(): array
    {
        return $this->properties;
    }
}
