<?php

declare(strict_types = 1);

namespace Maispace\MaiSeo\Event;

final class AfterMetaDescriptionRenderedEvent
{
    public function __construct(private string $description)
    {
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }
}
