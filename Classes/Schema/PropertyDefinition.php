<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Schema;

final class PropertyDefinition
{
    public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly array $expectedTypes,
        public readonly string $description = '',
    ) {}
}
