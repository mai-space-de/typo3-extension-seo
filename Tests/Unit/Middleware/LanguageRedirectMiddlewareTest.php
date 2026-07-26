<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\Middleware;

use Maispace\MaiSeo\Middleware\LanguageRedirectMiddleware;
use Maispace\MaiSeo\Service\AcceptLanguageNegotiator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Routing\SiteRouteResult;
use TYPO3\CMS\Core\Settings\Settings;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;

final class LanguageRedirectMiddlewareTest extends TestCase
{
    #[Test]
    public function redirectsDefaultRootToBestMatchWithoutCookie(): void
    {
        $response = $this->process(
            path: '/',
            acceptLanguage: 'en-GB,en;q=0.9',
            cookies: [],
            tail: '',
        );

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('https://www.example.org/en/', $response->getHeaderLine('Location'));
        self::assertStringContainsString('mai_seo_lang=1', $response->getHeaderLine('Set-Cookie'));
    }

    #[Test]
    public function doesNotRedirectWhenPreferenceCookieIsPresent(): void
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())
            ->method('handle')
            ->willReturn(new HtmlResponse('ok'));

        $response = $this->process(
            path: '/',
            acceptLanguage: 'en-GB,en;q=0.9',
            cookies: ['mai_seo_lang' => '0'],
            tail: '',
            handler: $handler,
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('', $response->getHeaderLine('Set-Cookie'));
    }

    #[Test]
    public function doesNotRedirectNonRootPathsWhenRootOnlyIsEnabled(): void
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())
            ->method('handle')
            ->willReturn(new HtmlResponse('ok'));

        $response = $this->process(
            path: '/unsere-angebote',
            acceptLanguage: 'en-GB,en;q=0.9',
            cookies: [],
            tail: 'unsere-angebote',
            handler: $handler,
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('mai_seo_lang=0', $response->getHeaderLine('Set-Cookie'));
    }

    #[Test]
    public function redirectsDeepDefaultPathWhenRootOnlyIsDisabled(): void
    {
        $response = $this->process(
            path: '/unsere-angebote',
            acceptLanguage: 'uk,en;q=0.5',
            cookies: [],
            tail: 'unsere-angebote',
            rootOnly: false,
        );

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('https://www.example.org/ua/unsere-angebote', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function doesNotRedirectWhenAlreadyOnMatchedLanguage(): void
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())
            ->method('handle')
            ->willReturn(new HtmlResponse('ok'));

        $response = $this->process(
            path: '/en/',
            acceptLanguage: 'en-GB,en;q=0.9',
            cookies: [],
            tail: '',
            languageId: 1,
            handler: $handler,
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('mai_seo_lang=1', $response->getHeaderLine('Set-Cookie'));
    }

    #[Test]
    public function doesNotRedirectWhenGermanTiedWithEnglishAtEqualQuality(): void
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())
            ->method('handle')
            ->willReturn(new HtmlResponse('ok'));

        $response = $this->process(
            path: '/',
            acceptLanguage: 'en-US,de-DE',
            cookies: [],
            tail: '',
            handler: $handler,
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('mai_seo_lang=0', $response->getHeaderLine('Set-Cookie'));
    }

    #[Test]
    public function doesNotRedirectWhenFeatureDisabled(): void
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())
            ->method('handle')
            ->willReturn(new HtmlResponse('ok'));

        $response = $this->process(
            path: '/',
            acceptLanguage: 'en-GB,en;q=0.9',
            cookies: [],
            tail: '',
            enabled: false,
            handler: $handler,
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('', $response->getHeaderLine('Set-Cookie'));
    }

    #[Test]
    public function doesNotRedirectPostRequests(): void
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())
            ->method('handle')
            ->willReturn(new HtmlResponse('ok'));

        $response = $this->process(
            path: '/',
            acceptLanguage: 'en-GB,en;q=0.9',
            cookies: [],
            tail: '',
            method: 'POST',
            handler: $handler,
        );

        self::assertSame(200, $response->getStatusCode());
    }

    private function process(
        string $path,
        string $acceptLanguage,
        array $cookies,
        string $tail,
        bool $enabled = true,
        bool $rootOnly = true,
        int $languageId = 0,
        string $method = 'GET',
        ?RequestHandlerInterface $handler = null,
    ): ResponseInterface {
        $site = $this->createSite($enabled, $rootOnly);
        $language = $this->languageFromSite($site, $languageId);
        $uri = new Uri('https://www.example.org' . $path);
        $routeResult = new SiteRouteResult($uri, $site, $language, $tail);

        $request = (new ServerRequest($uri, $method))
            ->withHeader('Accept-Language', $acceptLanguage)
            ->withCookieParams($cookies)
            ->withAttribute('site', $site)
            ->withAttribute('language', $language)
            ->withAttribute('routing', $routeResult);

        $handler ??= new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new HtmlResponse('ok');
            }
        };

        return (new LanguageRedirectMiddleware(new AcceptLanguageNegotiator()))->process($request, $handler);
    }

    private function createSite(bool $enabled, bool $rootOnly): Site
    {
        $languages = [
            $this->createLanguage(0, 'de-DE', 'de_DE', '/'),
            $this->createLanguage(1, 'en-GB', 'en_US', '/en/'),
            $this->createLanguage(2, 'uk-UA', 'uk_UA', '/ua/'),
            $this->createLanguage(3, 'ar-SA', 'ar_SA', '/ar/'),
        ];

        $settings = SiteSettings::create(new Settings([
            'seo.languageRedirect.enabled' => $enabled,
            'seo.languageRedirect.rootOnly' => $rootOnly,
            'seo.languageRedirect.cookieName' => 'mai_seo_lang',
            'seo.languageRedirect.cookieLifetime' => 31536000,
        ]));

        return new Site('bgm', 1, [
            'base' => 'https://www.example.org/',
            'languages' => array_map(
                static fn(SiteLanguage $language): array => [
                    'languageId' => $language->getLanguageId(),
                    'locale' => (string) $language->getLocale(),
                    'title' => $language->getTitle(),
                    'enabled' => $language->isEnabled(),
                    'base' => (string) $language->getBase(),
                    'hreflang' => $language->getHreflang(true),
                ],
                $languages,
            ),
        ], $settings);
    }

    private function createLanguage(int $id, string $hreflang, string $locale, string $base): SiteLanguage
    {
        return new SiteLanguage(
            $id,
            $locale,
            new Uri($base),
            [
                'title' => $hreflang,
                'enabled' => true,
                'hreflang' => $hreflang,
            ],
        );
    }

    private function languageFromSite(Site $site, int $languageId): SiteLanguage
    {
        return $site->getLanguageById($languageId);
    }
}
