<?php

declare(strict_types = 1);

namespace Maispace\MaiSeo\Event;

final class BeforeOpenGraphRenderedEvent
{
    /**
     * @param list<array{property: string, content: string}> $properties
     */
    public function __construct(
        private array $properties,
        private bool $enabled = true
    ) {
    }

    /**
     * @return list<array{property: string, content: string}>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @param list<array{property: string, content: string}> $properties
     */
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
