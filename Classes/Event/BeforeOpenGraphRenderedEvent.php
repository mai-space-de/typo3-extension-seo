<?php

declare(strict_types=1);

namespace Maispace\MaispacesSeo\Event;

final class BeforeOpenGraphRenderedEvent
{
    public function __construct(
        private array $properties,
        private bool $enabled = true
    ) {}

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function setProperties(array $properties): void
    {
        $this->properties = $properties;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }
}
