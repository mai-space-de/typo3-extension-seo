<?php

declare(strict_types = 1);

namespace Maispace\MaiSeo\Event;

final class BeforeLlmsTxtRenderedEvent
{
    /**
     * @param array<string, mixed> $pageRecord
     */
    public function __construct(
        private string $url,
        private readonly array $pageRecord,
        private bool $enabled = true
    ) {
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
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
