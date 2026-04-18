<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Event;

use Maispace\MaiSeo\Schema\VocabularyRegistryInterface;

final class VocabularyLoadedEvent
{
    public function __construct(
        private readonly VocabularyRegistryInterface $registry,
    ) {}

    public function getRegistry(): VocabularyRegistryInterface
    {
        return $this->registry;
    }
}
