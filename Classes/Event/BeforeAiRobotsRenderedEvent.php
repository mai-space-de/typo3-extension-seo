<?php

declare(strict_types = 1);

namespace Maispace\MaispacesSeo\Event;

final class BeforeAiRobotsRenderedEvent
{
    /**
     * @param list<array{name: string, content: string}> $tags
     * @param array<string, mixed>                       $pageRecord
     */
    public function __construct(
        private array $tags,
        private readonly array $pageRecord,
        private bool $enabled = true
    ) {
    }

    /**
     * @return list<array{name: string, content: string}>
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param list<array{name: string, content: string}> $tags
     */
    public function setTags(array $tags): void
    {
        $this->tags = $tags;
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
