<?php

declare(strict_types = 1);

namespace Maispace\MaispacesSeo\Event;

final class BeforeMetaDescriptionRenderedEvent
{
    /**
     * @param array<string, mixed> $pageRecord
     */
    public function __construct(
        private string $description,
        private readonly array $pageRecord,
        private bool $enabled = true
    ) {
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPageRecord(): array
    {
        return $this->pageRecord;
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
