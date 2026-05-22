<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Schema;

use Maispace\MaiSeo\Event\VocabularyLoadedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\SingletonInterface;

final class VocabularyRegistry implements VocabularyRegistryInterface, SingletonInterface
{
    /** @var array<string, TypeDefinition> */
    private array $types = [];

    /** @var array<string, PropertyDefinition> */
    private array $properties = [];

    private bool $loaded = false;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function getTypeNames(): array
    {
        $this->ensureLoaded();
        $names = array_keys($this->types);
        sort($names);
        return $names;
    }

    public function getType(string $typeName): ?TypeDefinition
    {
        $this->ensureLoaded();
        return $this->types[$typeName] ?? null;
    }

    public function getPropertiesForType(string $typeName): array
    {
        $this->ensureLoaded();
        $type = $this->types[$typeName] ?? null;
        if ($type === null) {
            return [];
        }

        $properties = $type->properties;
        foreach ($type->ancestors as $ancestor) {
            $ancestorType = $this->types[$ancestor] ?? null;
            if ($ancestorType !== null) {
                $properties = array_unique(array_merge($properties, $ancestorType->properties));
            }
        }

        return $properties;
    }

    public function getProperty(string $propertyName): ?PropertyDefinition
    {
        $this->ensureLoaded();
        return $this->properties[$propertyName] ?? null;
    }

    public function registerType(TypeDefinition $type): void
    {
        $this->types[$type->name] = $type;
    }

    public function extendType(string $typeName, array $additionalProperties): void
    {
        $existing = $this->types[$typeName] ?? null;
        if ($existing === null) {
            return;
        }
        $this->types[$typeName] = new TypeDefinition(
            name: $existing->name,
            label: $existing->label,
            ancestors: $existing->ancestors,
            properties: array_unique(array_merge($existing->properties, $additionalProperties)),
            description: $existing->description,
        );
    }

    private function ensureLoaded(): void
    {
        if ($this->loaded) {
            return;
        }
        $this->loadBuiltinVocabulary();
        $this->eventDispatcher->dispatch(new VocabularyLoadedEvent($this));
        $this->loaded = true;
    }

    private function loadBuiltinVocabulary(): void
    {
        $vocabFile = __DIR__ . '/../../Resources/Public/JavaScript/schema/vocabulary.json';
        if (!file_exists($vocabFile)) {
            $this->loadFallbackVocabulary();
            return;
        }

        $data = json_decode((string) file_get_contents($vocabFile), true);
        if (!is_array($data)) {
            $this->loadFallbackVocabulary();
            return;
        }

        foreach ($data['types'] ?? [] as $typeName => $typeDef) {
            $this->types[$typeName] = new TypeDefinition(
                name: $typeName,
                label: $typeDef['label'] ?? $typeName,
                ancestors: $typeDef['ancestors'] ?? [],
                properties: $typeDef['properties'] ?? [],
                description: $typeDef['description'] ?? '',
            );
        }

        foreach ($data['properties'] ?? [] as $propName => $propDef) {
            $this->properties[$propName] = new PropertyDefinition(
                name: $propName,
                label: $propDef['label'] ?? $propName,
                expectedTypes: $propDef['expectedTypes'] ?? ['Text'],
                description: $propDef['description'] ?? '',
            );
        }
    }

    private function loadFallbackVocabulary(): void
    {
        $fallbackTypes = [
            'Thing' => ['label' => 'Thing', 'ancestors' => [], 'properties' => ['name', 'description', 'url', 'image', 'identifier', 'sameAs']],
            'WebPage' => ['label' => 'WebPage', 'ancestors' => ['Thing', 'CreativeWork'], 'properties' => ['name', 'url', 'description', 'breadcrumb', 'mainEntity', 'author', 'datePublished', 'dateModified', 'image', 'inLanguage', 'keywords', 'isPartOf']],
            'Article' => ['label' => 'Article', 'ancestors' => ['Thing', 'CreativeWork'], 'properties' => ['headline', 'name', 'author', 'datePublished', 'dateModified', 'image', 'description', 'articleBody', 'wordCount', 'publisher']],
            'Organization' => ['label' => 'Organization', 'ancestors' => ['Thing'], 'properties' => ['name', 'url', 'logo', 'address', 'telephone', 'email', 'sameAs', 'description']],
            'LocalBusiness' => ['label' => 'LocalBusiness', 'ancestors' => ['Thing', 'Organization'], 'properties' => ['name', 'url', 'address', 'telephone', 'openingHours', 'geo', 'priceRange', 'image']],
            'Person' => ['label' => 'Person', 'ancestors' => ['Thing'], 'properties' => ['name', 'url', 'image', 'email', 'jobTitle', 'worksFor', 'sameAs']],
            'Event' => ['label' => 'Event', 'ancestors' => ['Thing'], 'properties' => ['name', 'startDate', 'endDate', 'location', 'description', 'image', 'url', 'organizer', 'performer', 'eventStatus', 'eventAttendanceMode']],
            'FAQPage' => ['label' => 'FAQPage', 'ancestors' => ['Thing', 'WebPage'], 'properties' => ['name', 'url', 'description', 'mainEntity']],
            'BreadcrumbList' => ['label' => 'BreadcrumbList', 'ancestors' => ['Thing'], 'properties' => ['itemListElement']],
            'AboutPage' => ['label' => 'AboutPage', 'ancestors' => ['Thing', 'WebPage'], 'properties' => ['name', 'url', 'description']],
            'ContactPage' => ['label' => 'ContactPage', 'ancestors' => ['Thing', 'WebPage'], 'properties' => ['name', 'url', 'description']],
            'Product' => ['label' => 'Product', 'ancestors' => ['Thing'], 'properties' => ['name', 'description', 'image', 'sku', 'brand', 'offers']],
            'WebSite' => ['label' => 'WebSite', 'ancestors' => ['Thing', 'CreativeWork'], 'properties' => ['name', 'url', 'description', 'inLanguage', 'potentialAction']],
        ];

        foreach ($fallbackTypes as $typeName => $typeDef) {
            $this->types[$typeName] = new TypeDefinition(
                name: $typeName,
                label: $typeDef['label'],
                ancestors: $typeDef['ancestors'],
                properties: $typeDef['properties'],
            );
        }
    }
}
