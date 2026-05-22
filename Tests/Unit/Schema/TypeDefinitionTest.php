<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\Schema;

use Maispace\MaiSeo\Schema\TypeDefinition;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TypeDefinitionTest extends TestCase
{
    #[Test]
    public function constructorSetsAllProperties(): void
    {
        $definition = new TypeDefinition(
            name: 'Article',
            label: 'Article',
            ancestors: ['Thing', 'CreativeWork'],
            properties: ['headline', 'author', 'datePublished'],
            description: 'An article, such as a news article or piece of investigative report.',
        );

        self::assertSame('Article', $definition->name);
        self::assertSame('Article', $definition->label);
        self::assertSame(['Thing', 'CreativeWork'], $definition->ancestors);
        self::assertSame(['headline', 'author', 'datePublished'], $definition->properties);
        self::assertSame('An article, such as a news article or piece of investigative report.', $definition->description);
    }

    #[Test]
    public function descriptionDefaultsToEmptyString(): void
    {
        $definition = new TypeDefinition(
            name: 'Thing',
            label: 'Thing',
            ancestors: [],
            properties: ['name', 'description'],
        );

        self::assertSame('', $definition->description);
    }

    #[Test]
    public function rootTypeHasEmptyAncestors(): void
    {
        $definition = new TypeDefinition(
            name: 'Thing',
            label: 'Thing',
            ancestors: [],
            properties: ['name', 'url'],
        );

        self::assertSame([], $definition->ancestors);
    }

    #[Test]
    public function propertiesCanBeEmpty(): void
    {
        $definition = new TypeDefinition(
            name: 'Empty',
            label: 'Empty',
            ancestors: ['Thing'],
            properties: [],
        );

        self::assertSame([], $definition->properties);
    }

    #[Test]
    public function propertiesAreReadonly(): void
    {
        $definition = new TypeDefinition(
            name: 'Person',
            label: 'Person',
            ancestors: ['Thing'],
            properties: ['name', 'email'],
        );

        $reflection = new \ReflectionClass($definition);
        self::assertTrue($reflection->getProperty('name')->isReadOnly());
        self::assertTrue($reflection->getProperty('label')->isReadOnly());
        self::assertTrue($reflection->getProperty('ancestors')->isReadOnly());
        self::assertTrue($reflection->getProperty('properties')->isReadOnly());
        self::assertTrue($reflection->getProperty('description')->isReadOnly());
    }
}
