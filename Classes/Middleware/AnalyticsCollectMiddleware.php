<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Middleware;

use Maispace\MaiSeo\Analytics\BackendUserDetector;
use Maispace\MaiSeo\Analytics\HitDraft;
use Maispace\MaiSeo\Analytics\HitRecorder;
use Maispace\MaiSeo\Analytics\PayloadParser;
use Maispace\MaiSeo\Analytics\TrackingContext;
use Maispace\MaiSeo\Analytics\TrackingDecision;
use Maispace\MaiSeo\Analytics\TrackingPolicy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Site\Entity\Site;

/**
 * Nimmt das Beacon entgegen. Backend-Benutzer werden serverseitig verworfen,
 * weil die gecachte Seite das Script für alle Besucher enthält.
 */
final class AnalyticsCollectMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly TrackingPolicy $policy,
        private readonly PayloadParser $parser,
        private readonly HitRecorder $recorder,
        private readonly BackendUserDetector $backendUserDetector,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->isCollectPath($request)) {
            return $handler->handle($request);
        }

        if (strtoupper($request->getMethod()) !== 'POST') {
            return $this->noContent();
        }

        $draft = $this->parser->parse((string) $request->getBody());
        if (!$draft instanceof HitDraft) {
            return $this->noContent();
        }

        $site = $request->getAttribute('site');
        $decision = $this->policy->decide(new TrackingContext(
            enabled: $site instanceof Site && (bool) $site->getSettings()->get('seo.analytics.enabled', true),
            backendUserLoggedIn: $this->backendUserDetector->isLoggedIn(),
            method: $request->getMethod(),
            userAgent: $request->getHeaderLine('User-Agent'),
            requestPath: $draft->path,
            sameOrigin: $this->isSameOrigin($request),
        ));

        if ($decision === TrackingDecision::Allow) {
            $this->recorder->record($request, $draft);
        }

        return $this->noContent();
    }

    private function isCollectPath(ServerRequestInterface $request): bool
    {
        $path = rtrim($request->getUri()->getPath(), '/');

        return str_ends_with($path, '/mai-seo/analytics');
    }

    private function isSameOrigin(ServerRequestInterface $request): bool
    {
        $origin = $request->getHeaderLine('Origin');
        if ($origin === '') {
            $origin = $request->getHeaderLine('Referer');
        }
        $host = parse_url($origin, PHP_URL_HOST);

        return is_string($host) && strcasecmp($host, $request->getUri()->getHost()) === 0;
    }

    private function noContent(): ResponseInterface
    {
        return (new Response())->withStatus(204)->withHeader('Cache-Control', 'no-store');
    }
}
