<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Event;

final class StructuredDataEditorConfigEvent
{
    public function __construct(
        public readonly int $pageUid,
        private array $editorConfig,
    ) {}

    public function getConfig(): array
    {
        return $this->editorConfig;
    }

    public function setConfig(array $config): void
    {
        $this->editorConfig = $config;
    }

    public function addType(string $typeName, array $properties): void
    {
        $this->editorConfig['propertiesByType'][$typeName] = $properties;
        if (!in_array($typeName, $this->editorConfig['types'] ?? [], true)) {
            $this->editorConfig['types'][] = $typeName;
        }
    }
}
