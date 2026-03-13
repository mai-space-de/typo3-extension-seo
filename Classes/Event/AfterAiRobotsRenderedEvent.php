<?php

declare(strict_types = 1);

namespace Maispace\MaispacesSeo\Event;

final class AfterAiRobotsRenderedEvent
{
    /**
     * @param list<array{name: string, content: string}> $tags
     */
    public function __construct(private array $tags)
    {
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
}
