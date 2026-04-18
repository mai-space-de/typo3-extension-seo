<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Event;

final class PageRecordSavedEvent
{
    public function __construct(
        public readonly int $pageUid,
        public readonly array $fieldArray,
        public readonly string $status,
    ) {}
}
