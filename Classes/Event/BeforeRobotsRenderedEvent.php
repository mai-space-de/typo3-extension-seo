<?php

declare(strict_types = 1);

namespace Maispace\MaispacesSeo\Event;

final class BeforeRobotsRenderedEvent
{
    /**
     * @param array<string, mixed> $pageRecord
     */
    public function __construct(
        private string $directives,
        private readonly array $pageRecord,
        private bool $enabled = true
    ) {
    }

    public function getDirectives(): string
    {
        return $this->directives;
    }

    public function setDirectives(string $directives): void
    {
        $this->directives = $directives;
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
