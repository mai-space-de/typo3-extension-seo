<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Middleware;

use Maispace\MaiSeo\Service\AcceptLanguageNegotiator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Routing\SiteRouteResult;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

/**
 * Redirects first-time visitors on the default language to the best Accept-Language match.
 *
 * Cookie presence means the visitor already chose or was negotiated — the language switcher
 * is never overridden. The cookie is only written when absent so page cache stays intact.
 * Runs after site resolution and before page resolution.
 */
final class LanguageRedirectMiddleware implements MiddlewareInterface
{
    public const DEFAULT_COOKIE_NAME = 'mai_seo_lang';
    public const DEFAULT_COOKIE_LIFETIME = 31536000;

    public function __construct(
        private readonly AcceptLanguageNegotiator $negotiator,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $site = $request->getAttribute('site');
        $language = $request->getAttribute('language');
        $routeResult = $request->getAttribute('routing');

        if (!$site instanceof Site || !$language instanceof SiteLanguage) {
            return $handler->handle($request);
        }

        $settings = $this->resolveSettings($site);
        if ($settings['enabled'] !== true) {
            return $handler->handle($request);
        }

        $cookieName = $settings['cookieName'];
        $hasPreference = array_key_exists($cookieName, $request->getCookieParams());

        if ($hasPreference || !$this->isSafeMethod($request)) {
            return $handler->handle($request);
        }

        if ($language->getLanguageId() !== $site->getDefaultLanguage()->getLanguageId()) {
            return $this->withPreferenceCookie($handler->handle($request), $language, $settings);
        }

        if ($settings['rootOnly'] && !$this->isLanguageRoot($routeResult)) {
            return $this->withPreferenceCookie($handler->handle($request), $language, $settings);
        }

        $acceptLanguage = $request->getHeaderLine('Accept-Language');
        $match = $this->negotiator->negotiate($acceptLanguage, $site->getLanguages());
        if (!$match instanceof SiteLanguage || $match->getLanguageId() === $language->getLanguageId()) {
            return $this->withPreferenceCookie($handler->handle($request), $language, $settings);
        }

        $targetUri = $this->buildTargetUri($request, $routeResult, $match);
        if ($this->urisPointToSamePath($request->getUri(), $targetUri)) {
            return $this->withPreferenceCookie($handler->handle($request), $language, $settings);
        }

        return $this->withPreferenceCookie(new RedirectResponse($targetUri, 302), $match, $settings);
    }

    /**
     * @return array{enabled: bool, rootOnly: bool, cookieName: string, cookieLifetime: int}
     */
    private function resolveSettings(Site $site): array
    {
        $settings = $site->getSettings();

        return [
            'enabled' => (bool) $settings->get('seo.languageRedirect.enabled', true),
            'rootOnly' => (bool) $settings->get('seo.languageRedirect.rootOnly', true),
            'cookieName' => (string) $settings->get('seo.languageRedirect.cookieName', self::DEFAULT_COOKIE_NAME),
            'cookieLifetime' => max(0, (int) $settings->get('seo.languageRedirect.cookieLifetime', self::DEFAULT_COOKIE_LIFETIME)),
        ];
    }

    private function isSafeMethod(ServerRequestInterface $request): bool
    {
        return in_array(strtoupper($request->getMethod()), ['GET', 'HEAD'], true);
    }

    private function isLanguageRoot(mixed $routeResult): bool
    {
        if (!$routeResult instanceof SiteRouteResult) {
            return false;
        }

        $tail = trim($routeResult->getTail(), '/');

        return $tail === '';
    }

    private function buildTargetUri(
        ServerRequestInterface $request,
        mixed $routeResult,
        SiteLanguage $targetLanguage,
    ): UriInterface {
        $basePath = rtrim($targetLanguage->getBase()->getPath(), '/');
        $tail = $routeResult instanceof SiteRouteResult ? ltrim($routeResult->getTail(), '/') : '';
        $path = $tail === '' ? ($basePath === '' ? '/' : $basePath . '/') : $basePath . '/' . $tail;

        $uri = $request->getUri()->withPath($path);
        $base = $targetLanguage->getBase();
        if ($base->getHost() !== '') {
            $uri = $uri->withHost($base->getHost());
            if ($base->getScheme() !== '') {
                $uri = $uri->withScheme($base->getScheme());
            }
            if ($base->getPort() !== null) {
                $uri = $uri->withPort($base->getPort());
            }
        }

        return $uri;
    }

    private function urisPointToSamePath(UriInterface $current, UriInterface $target): bool
    {
        return rtrim($current->getPath(), '/') === rtrim($target->getPath(), '/');
    }

    /**
     * @param array{enabled: bool, rootOnly: bool, cookieName: string, cookieLifetime: int} $settings
     */
    private function withPreferenceCookie(
        ResponseInterface $response,
        SiteLanguage $language,
        array $settings,
    ): ResponseInterface {
        $cookie = Cookie::create(
            $settings['cookieName'],
            (string) $language->getLanguageId(),
            time() + $settings['cookieLifetime'],
            '/',
            null,
            null,
            true,
            false,
            Cookie::SAMESITE_LAX,
        );

        return $response->withAddedHeader('Set-Cookie', (string) $cookie);
    }
}
