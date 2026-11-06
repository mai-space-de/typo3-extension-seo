<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\Middleware;

use Maispace\MaiSeo\Analytics\BackendUserDetector;
use Maispace\MaiSeo\Analytics\HitRecorder;
use Maispace\MaiSeo\Analytics\PayloadParser;
use Maispace\MaiSeo\Analytics\TrackingPolicy;
use Maispace\MaiSeo\Middleware\AnalyticsCollectMiddleware;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Settings\Settings;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;

final class AnalyticsCollectMiddlewareTest extends TestCase
{
    #[Test]
    public function ignoresUnrelatedRequests(): void
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->willReturn(new HtmlResponse('ok'));
        $recorder = $this->createMock(HitRecorder::class);
        $recorder->expects(self::never())->method('record');

        $response = $this->middleware($recorder, false)->process(
            new ServerRequest(new Uri('https://www.example.org/angebote'), 'GET'),
            $handler,
        );

        self::assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function recordsAnonymousSameOriginPageview(): void
    {
        $recorder = $this->createMock(HitRecorder::class);
        $recorder->expects(self::once())->method('record');

        $response = $this->middleware($recorder, false)->process($this->collectRequest(), $this->failingHandler());

        self::assertSame(204, $response->getStatusCode());
    }

    #[Test]
    public function dropsBackendUserPageviews(): void
    {
        $recorder = $this->createMock(HitRecorder::class);
        $recorder->expects(self::never())->method('record');

        $response = $this->middleware($recorder, true)->process($this->collectRequest(), $this->failingHandler());

        self::assertSame(204, $response->getStatusCode());
        self::assertSame('no-store', $response->getHeaderLine('Cache-Control'));
    }

    private function middleware(HitRecorder $recorder, bool $backendUser): AnalyticsCollectMiddleware
    {
        return new AnalyticsCollectMiddleware(
            new TrackingPolicy(),
            new PayloadParser(),
            $recorder,
            new class ($backendUser) implements BackendUserDetector {
                public function __construct(private bool $loggedIn) {}

                public function isLoggedIn(): bool
                {
                    return $this->loggedIn;
                }
            },
        );
    }

    private function collectRequest(): ServerRequestInterface
    {
        $settings = SiteSettings::create(new Settings([
            'seo.analytics.enabled' => true,
        ]));
        $site = new Site('bgm', 1, [
            'base' => 'https://www.example.org/',
            'languages' => [
                [
                    'languageId' => 0,
                    'locale' => 'de_DE',
                    'title' => 'Deutsch',
                    'enabled' => true,
                    'base' => '/',
                ],
            ],
        ], $settings);

        $body = json_encode([
            'pageId' => 3,
            'path' => '/angebote',
            'hitToken' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
            'duration' => 0,
        ], JSON_THROW_ON_ERROR);

        $stream = new Stream('php://temp', 'r+');
        $stream->write($body);
        $stream->rewind();

        return (new ServerRequest(new Uri('https://www.example.org/mai-seo/analytics'), 'POST'))
            ->withHeader('Origin', 'https://www.example.org')
            ->withHeader('User-Agent', 'Mozilla/5.0')
            ->withBody($stream)
            ->withAttribute('site', $site);
    }

    private function failingHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new \RuntimeException('Collect-Pfad darf nicht an TYPO3 weitergereicht werden.');
            }
        };
    }
}
