<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\Service;

use Maispace\MaiSeo\Service\AcceptLanguageNegotiator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

final class AcceptLanguageNegotiatorTest extends TestCase
{
    private AcceptLanguageNegotiator $subject;

    protected function setUp(): void
    {
        $this->subject = new AcceptLanguageNegotiator();
    }

    #[Test]
    public function parseAcceptLanguageOrdersByQuality(): void
    {
        self::assertSame(
            ['en-gb', 'de', 'en', 'fr'],
            $this->subject->parseAcceptLanguage('en-GB,en;q=0.8,de;q=0.9,fr;q=0.1'),
        );
    }

    #[Test]
    public function parseAcceptLanguageDropsZeroQuality(): void
    {
        self::assertSame(
            ['de'],
            $this->subject->parseAcceptLanguage('en;q=0,de;q=0.5'),
        );
    }

    #[Test]
    public function negotiateMatchesExactHreflang(): void
    {
        $match = $this->subject->negotiate('en-GB,en;q=0.8', $this->languages());

        self::assertInstanceOf(SiteLanguage::class, $match);
        self::assertSame(1, $match->getLanguageId());
    }

    #[Test]
    public function negotiateMatchesPrimaryLanguageSubtag(): void
    {
        $match = $this->subject->negotiate('uk,en;q=0.5', $this->languages());

        self::assertInstanceOf(SiteLanguage::class, $match);
        self::assertSame(2, $match->getLanguageId());
    }

    #[Test]
    public function negotiateMatchesArabicPrimary(): void
    {
        $match = $this->subject->negotiate('ar-EG,ar;q=0.9', $this->languages());

        self::assertInstanceOf(SiteLanguage::class, $match);
        self::assertSame(3, $match->getLanguageId());
    }

    #[Test]
    public function negotiateReturnsNullForEmptyHeader(): void
    {
        self::assertNull($this->subject->negotiate('', $this->languages()));
    }

    #[Test]
    public function negotiateReturnsNullWhenNoLanguageMatches(): void
    {
        self::assertNull($this->subject->negotiate('fr-FR,fr;q=0.9', $this->languages()));
    }

    #[Test]
    public function negotiateSkipsDisabledLanguages(): void
    {
        $languages = [
            $this->language(0, 'de-DE', 'de_DE', true),
            $this->language(1, 'en-GB', 'en_US', false),
        ];

        self::assertNull($this->subject->negotiate('en-GB', $languages));
    }

    #[Test]
    #[DataProvider('localeFallbackProvider')]
    public function negotiateFallsBackToLocaleName(string $header, int $expectedLanguageId): void
    {
        $match = $this->subject->negotiate($header, $this->languages());

        self::assertInstanceOf(SiteLanguage::class, $match);
        self::assertSame($expectedLanguageId, $match->getLanguageId());
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function localeFallbackProvider(): array
    {
        return [
            'german primary' => ['de', 0],
            'english us maps via primary' => ['en-US,en;q=0.9', 1],
        ];
    }

    /**
     * @return list<SiteLanguage>
     */
    private function languages(): array
    {
        return [
            $this->language(0, 'de-DE', 'de_DE'),
            $this->language(1, 'en-GB', 'en_US'),
            $this->language(2, 'uk-UA', 'uk_UA'),
            $this->language(3, 'ar-SA', 'ar_SA'),
        ];
    }

    private function language(
        int $languageId,
        string $hreflang,
        string $locale,
        bool $enabled = true,
    ): SiteLanguage {
        return new SiteLanguage(
            $languageId,
            $locale,
            new Uri($languageId === 0 ? '/' : '/' . ($languageId === 2 ? 'ua' : ($languageId === 3 ? 'ar' : 'en')) . '/'),
            [
                'title' => $hreflang,
                'enabled' => $enabled,
                'hreflang' => $hreflang,
            ],
        );
    }
}
