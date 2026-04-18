<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\Utility;

use Maispace\MaiSeo\Utility\JsonMerge;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class JsonMergeTest extends TestCase
{
    #[Test]
    public function deepMergeOverridesScalarValues(): void
    {
        $base = ['name' => 'Original', 'url' => 'https://example.com'];
        $overrides = ['name' => 'Overridden'];

        $result = JsonMerge::deepMerge($base, $overrides);

        self::assertSame('Overridden', $result['name']);
        self::assertSame('https://example.com', $result['url']);
    }

    #[Test]
    public function deepMergeRecursivelyMergesArrays(): void
    {
        $base = ['address' => ['city' => 'Berlin', 'country' => 'DE']];
        $overrides = ['address' => ['city' => 'Cologne']];

        $result = JsonMerge::deepMerge($base, $overrides);

        self::assertSame('Cologne', $result['address']['city']);
        self::assertSame('DE', $result['address']['country']);
    }

    #[Test]
    public function deepMergeRemovesSentinelKeys(): void
    {
        $base = ['name' => 'Test', 'datePublished' => '2024-01-01'];
        $overrides = ['datePublished' => JsonMerge::REMOVE_SENTINEL];

        $result = JsonMerge::deepMerge($base, $overrides);

        self::assertSame('Test', $result['name']);
        self::assertArrayNotHasKey('datePublished', $result);
    }

    #[Test]
    public function deepMergeRemovesSentinelsInNestedArrays(): void
    {
        $base = ['address' => ['city' => 'Berlin', 'zip' => '10115']];
        $overrides = ['address' => ['zip' => JsonMerge::REMOVE_SENTINEL]];

        $result = JsonMerge::deepMerge($base, $overrides);

        self::assertSame('Berlin', $result['address']['city']);
        self::assertArrayNotHasKey('zip', $result['address']);
    }

    #[Test]
    public function deepMergeAddsNewKeysFromOverrides(): void
    {
        $base = ['name' => 'Test'];
        $overrides = ['description' => 'New description'];

        $result = JsonMerge::deepMerge($base, $overrides);

        self::assertSame('Test', $result['name']);
        self::assertSame('New description', $result['description']);
    }

    #[Test]
    public function deepMergeWithEmptyOverridesReturnsBase(): void
    {
        $base = ['name' => 'Test', 'url' => 'https://example.com'];

        $result = JsonMerge::deepMerge($base, []);

        self::assertSame($base, $result);
    }

    #[Test]
    public function deepMergeWithEmptyBaseReturnsOverrides(): void
    {
        $overrides = ['name' => 'Test'];

        $result = JsonMerge::deepMerge([], $overrides);

        self::assertSame($overrides, $result);
    }
}
