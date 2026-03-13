<?php

declare(strict_types = 1);

namespace Maispace\MaispacesSeo\Event;

final class BeforeCanonicalRenderedEvent
{
    /**
     * @param array<string, mixed> $pageRecord
     */
    public function __construct(
        private string $canonicalUrl,
        private readonly array $pageRecord,
        private bool $enabled = true
    ) {
    }

    public function getCanonicalUrl(): string
    {
        return $this->canonicalUrl;
    }

    public function setCanonicalUrl(string $canonicalUrl): void
    {
        $this->canonicalUrl = $canonicalUrl;
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
