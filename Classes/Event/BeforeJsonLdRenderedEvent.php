<?php

declare(strict_types=1);

namespace Maispace\MaispacesSeo\Event;

final class BeforeJsonLdRenderedEvent
{
    /**
     * @param array<string, mixed> $schema
     */
    public function __construct(
        private array $schema,
        private bool $enabled = true
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getSchema(): array
    {
        return $this->schema;
    }

    /**
     * @param array<string, mixed> $schema
     */
    public function setSchema(array $schema): void
    {
        $this->schema = $schema;
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
