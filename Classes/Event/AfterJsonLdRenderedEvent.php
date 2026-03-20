<?php

declare(strict_types = 1);

namespace Maispace\MaiSeo\Event;

final class AfterJsonLdRenderedEvent
{
    public function __construct(private string $script)
    {
    }

    public function getScript(): string
    {
        return $this->script;
    }

    public function setScript(string $script): void
    {
        $this->script = $script;
    }
}
