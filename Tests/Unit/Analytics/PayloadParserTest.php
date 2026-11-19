<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\Analytics;

use Maispace\MaiSeo\Analytics\PayloadParser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PayloadParserTest extends TestCase
{
    private const TOKEN = '0123456789abcdef0123';

    #[Test]
    public function longMultibyteTitleIsCutAtACharacterBoundary(): void
    {
        // "р" is D1 80 in UTF-8, so byte 255 falls into the middle of a character.
        $title = str_repeat('а', 126) . str_repeat('р', 30);

        $draft = $this->parse(['title' => $title]);

        self::assertNotNull($draft);
        self::assertTrue(mb_check_encoding($draft->title, 'UTF-8'));
        self::assertLessThanOrEqual(255, strlen($draft->title));
        self::assertStringStartsWith(str_repeat('а', 126), $draft->title);
    }

    #[Test]
    public function longArabicPathStaysValidUtf8(): void
    {
        $path = '/' . str_repeat('ع', 400);

        $draft = $this->parse(['path' => $path]);

        self::assertNotNull($draft);
        self::assertTrue(mb_check_encoding($draft->path, 'UTF-8'));
        self::assertLessThanOrEqual(512, strlen($draft->path));
    }

    #[Test]
    public function shortValuesAreKeptUnchanged(): void
    {
        $draft = $this->parse(['title' => 'Kurzer Titel']);

        self::assertNotNull($draft);
        self::assertSame('Kurzer Titel', $draft->title);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function parse(array $overrides): ?\Maispace\MaiSeo\Analytics\HitDraft
    {
        $payload = array_merge([
            'pageId' => 12,
            'hitToken' => self::TOKEN,
            'path' => '/seite',
            'title' => 'Titel',
        ], $overrides);

        return (new PayloadParser())->parse((string) json_encode($payload, JSON_UNESCAPED_UNICODE));
    }
}
