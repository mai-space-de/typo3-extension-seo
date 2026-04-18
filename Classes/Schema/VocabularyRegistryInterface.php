<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Schema;

interface VocabularyRegistryInterface
{
    public function getTypeNames(): array;

    public function getType(string $typeName): ?TypeDefinition;

    public function getPropertiesForType(string $typeName): array;

    public function getProperty(string $propertyName): ?PropertyDefinition;

    public function registerType(TypeDefinition $type): void;

    public function extendType(string $typeName, array $additionalProperties): void;
}
