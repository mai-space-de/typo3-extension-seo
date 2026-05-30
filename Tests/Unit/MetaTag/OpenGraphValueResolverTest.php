<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\MetaTag;

use Maispace\MaiSeo\MetaTag\OpenGraphValueResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class OpenGraphValueResolverTest extends TestCase
{
    private OpenGraphValueResolver $subject;

    protected function setUp(): void
    {
        $this->subject = new OpenGraphValueResolver();
    }

    #[Test]
    public function resolveTitlePrefersEditorOverride(): void
    {
        self::assertSame(
            'Custom OG title',
            $this->subject->resolveTitle([
                'tx_maiseo_og_title' => 'Custom OG title',
                'title' => 'Page title',
            ], 'Site name'),
        );
    }

    #[Test]
    public function resolveTitleFallsBackToPageTitle(): void
    {
        self::assertSame(
            'Page title',
            $this->subject->resolveTitle([
                'title' => 'Page title',
            ], 'Site name'),
        );
    }

    #[Test]
    public function resolveTitleFallsBackToSiteName(): void
    {
        self::assertSame(
            'Site name',
            $this->subject->resolveTitle([], 'Site name'),
        );
    }

    #[Test]
    public function resolveDescriptionPrefersEditorOverride(): void
    {
        self::assertSame(
            'Custom OG description',
            $this->subject->resolveDescription([
                'tx_maiseo_og_description' => 'Custom OG description',
                'description' => 'Page description',
            ]),
        );
    }

    #[Test]
    public function resolveDescriptionFallsBackToPageDescription(): void
    {
        self::assertSame(
            'Page description',
            $this->subject->resolveDescription([
                'description' => 'Page description',
            ]),
        );
    }

    #[Test]
    #[DataProvider('schemaTypeProvider')]
    public function resolveOpenGraphTypeMapsSchemaTypes(string $schemaType, string $expected): void
    {
        self::assertSame(
            $expected,
            $this->subject->resolveOpenGraphType([
                'tx_maiseo_schema_type' => $schemaType,
            ]),
        );
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function schemaTypeProvider(): iterable
    {
        yield 'article' => ['Article', 'article'];
        yield 'person' => ['Person', 'profile'];
        yield 'website' => ['WebSite', 'website'];
        yield 'webpage' => ['WebPage', 'website'];
        yield 'unknown defaults to website' => ['CustomType', 'website'];
    }

    #[Test]
    public function resolveImageFieldCandidatesPrefersOgImage(): void
    {
        self::assertSame(
            ['tx_maiseo_og_image', 'media'],
            $this->subject->resolveImageFieldCandidates([
                'tx_maiseo_og_image' => 1,
                'media' => 2,
            ]),
        );
    }

    #[Test]
    public function resolveImageFieldCandidatesFallsBackToMediaOnly(): void
    {
        self::assertSame(
            ['media'],
            $this->subject->resolveImageFieldCandidates([
                'media' => 2,
            ]),
        );
    }

    #[Test]
    public function resolveTwitterCardUsesLargeImageWhenImageExists(): void
    {
        self::assertSame('summary_large_image', $this->subject->resolveTwitterCard(true));
    }

    #[Test]
    public function resolveTwitterCardUsesSummaryWithoutImage(): void
    {
        self::assertSame('summary', $this->subject->resolveTwitterCard(false));
    }
}
