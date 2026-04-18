<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\StructuredData\Collector;

use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;

interface CollectorInterface
{
    public function collect(StructuredDataCollectionEvent $event): void;

    public function supportedTypes(): array;

    public function priority(): int;
}
