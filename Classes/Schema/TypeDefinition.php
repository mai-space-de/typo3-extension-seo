<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Schema;

final class TypeDefinition
{
    public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly array $ancestors,
        public readonly array $properties,
        public readonly string $description = '',
    ) {}
}
