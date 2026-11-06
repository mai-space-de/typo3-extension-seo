<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\Analytics;

use Maispace\MaiSeo\Analytics\ChannelClassifier;
use Maispace\MaiSeo\Analytics\ClientClassifier;
use Maispace\MaiSeo\Analytics\PayloadParser;
use Maispace\MaiSeo\Analytics\VisitorHasher;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TrackingInputTest extends TestCase
{
    #[Test]
    public function parsesAPageviewAndCapsDuration(): void
    {
        $draft = (new PayloadParser())->parse((string) json_encode([
            'pageId' => 12,
            'languageId' => 1,
            'locale' => 'en-GB',
            'path' => '/en/offers',
            'title' => '<b>Offers</b>',
            'referrer' => 'https://www.google.com/search?q=bgm',
            'screen' => 390,
            'hitToken' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
            'duration' => 9000,
        ]));

        self::assertNotNull($draft);
        self::assertSame(12, $draft->pageId);
        self::assertSame('Offers', $draft->title);
        self::assertSame(1800, $draft->duration);
    }

    #[Test]
    public function rejectsInvalidPayloads(): void
    {
        $parser = new PayloadParser();

        self::assertNull($parser->parse(''));
        self::assertNull($parser->parse('{"pageId":0,"hitToken":"aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee","path":"/"}'));
        self::assertNull($parser->parse('{"pageId":4,"hitToken":"short","path":"/"}'));
        self::assertNull($parser->parse('{"pageId":4,"hitToken":"aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee","path":"relative"}'));
    }

    #[Test]
    public function classifiesChannelDeviceAndBrowser(): void
    {
        $channels = new ChannelClassifier();
        $clients = new ClientClassifier();

        self::assertSame('direct', $channels->classify('', 'www.bgm-pulheim.org'));
        self::assertSame('direct', $channels->classify('https://www.bgm-pulheim.org/news', 'www.bgm-pulheim.org'));
        self::assertSame('referral', $channels->classify('https://evil-bgm-pulheim.org/', 'bgm-pulheim.org'));
        self::assertSame('search', $channels->classify('https://www.google.de/search?q=bgm', 'www.bgm-pulheim.org'));
        self::assertSame('social', $channels->classify('https://x.com/bgm', 'www.bgm-pulheim.org'));
        self::assertSame('campaign', $channels->classify('https://news.example.org/a?utm_source=nl', 'www.bgm-pulheim.org'));

        self::assertSame('mobile', $clients->device(390, ''));
        self::assertSame('tablet', $clients->device(800, ''));
        self::assertSame('desktop', $clients->device(1440, ''));
        self::assertSame('tablet', $clients->device(0, 'Mozilla/5.0 (iPad)'));
        self::assertSame('Edge', $clients->browser('Mozilla/5.0 Edg/120.0'));
        self::assertSame('Chrome', $clients->browser('Mozilla/5.0 Chrome/120.0'));
        self::assertSame('Safari', $clients->browser('Mozilla/5.0 Version/17.0 Safari/605.1.15'));
    }

    #[Test]
    public function visitorHashChangesWithTheDayAndDoesNotExposeTheIp(): void
    {
        $hasher = new VisitorHasher();
        $today = $hasher->hash('203.0.113.10', 'Mozilla/5.0', '2026-11-06', 'salt');
        $tomorrow = $hasher->hash('203.0.113.10', 'Mozilla/5.0', '2026-11-07', 'salt');

        self::assertSame($today, $hasher->hash('203.0.113.10', 'Mozilla/5.0', '2026-11-06', 'salt'));
        self::assertNotSame($today, $tomorrow);
        self::assertStringNotContainsString('203.0.113.10', $today);
    }
}
