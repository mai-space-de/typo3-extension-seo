<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\Schema;

use Maispace\MaiSeo\Schema\PropertyDefinition;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PropertyDefinitionTest extends TestCase
{
    #[Test]
    public function constructorSetsAllProperties(): void
    {
        $definition = new PropertyDefinition(
            name: 'name',
            label: 'Name',
            expectedTypes: ['Text', 'URL'],
            description: 'The name of the item.',
        );

        self::assertSame('name', $definition->name);
        self::assertSame('Name', $definition->label);
        self::assertSame(['Text', 'URL'], $definition->expectedTypes);
        self::assertSame('The name of the item.', $definition->description);
    }

    #[Test]
    public function descriptionDefaultsToEmptyString(): void
    {
        $definition = new PropertyDefinition(
            name: 'url',
            label: 'URL',
            expectedTypes: ['URL'],
        );

        self::assertSame('', $definition->description);
    }

    #[Test]
    public function expectedTypesCanBeEmpty(): void
    {
        $definition = new PropertyDefinition(
            name: 'custom',
            label: 'Custom',
            expectedTypes: [],
        );

        self::assertSame([], $definition->expectedTypes);
    }

    #[Test]
    public function propertiesAreReadonly(): void
    {
        $definition = new PropertyDefinition(
            name: 'image',
            label: 'Image',
            expectedTypes: ['ImageObject', 'URL'],
            description: 'An image of the item.',
        );

        // Verify readonly by confirming the values are immutable via reflection
        $reflection = new \ReflectionClass($definition);
        self::assertTrue($reflection->getProperty('name')->isReadOnly());
        self::assertTrue($reflection->getProperty('label')->isReadOnly());
        self::assertTrue($reflection->getProperty('expectedTypes')->isReadOnly());
        self::assertTrue($reflection->getProperty('description')->isReadOnly());
    }
}
