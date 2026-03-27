<?php

declare(strict_types = 1);

namespace Maispace\MaiSeo\Event;

final class AfterLlmsTxtRenderedEvent
{
    public function __construct(private string $tag)
    {
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function setTag(string $tag): void
    {
        $this->tag = $tag;
    }
}
