<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\Analytics;

use Maispace\MaiSeo\Analytics\TrackingContext;
use Maispace\MaiSeo\Analytics\TrackingDecision;
use Maispace\MaiSeo\Analytics\TrackingPolicy;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TrackingPolicyTest extends TestCase
{
    private TrackingPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new TrackingPolicy();
    }

    #[Test]
    public function allowsAnonymousSameOriginPageview(): void
    {
        self::assertSame(TrackingDecision::Allow, $this->policy->decide($this->context()));
    }

    #[Test]
    public function rejectsLoggedInBackendUser(): void
    {
        self::assertSame(
            TrackingDecision::BackendUser,
            $this->policy->decide($this->context(backendUserLoggedIn: true)),
        );
    }

    #[Test]
    public function rejectsBotsPreviewAndForeignOrigin(): void
    {
        self::assertSame(TrackingDecision::Bot, $this->policy->decide($this->context(userAgent: 'Mozilla/5.0 (compatible; Googlebot/2.1)')));
        self::assertSame(TrackingDecision::Bot, $this->policy->decide($this->context(userAgent: '')));
        self::assertSame(TrackingDecision::Preview, $this->policy->decide($this->context(requestPath: '/seite?ADMCMD_prev=1')));
        self::assertSame(TrackingDecision::Origin, $this->policy->decide($this->context(sameOrigin: false)));
        self::assertSame(TrackingDecision::Disabled, $this->policy->decide($this->context(enabled: false)));
        self::assertSame(TrackingDecision::Method, $this->policy->decide($this->context(method: 'GET')));
    }

    private function context(
        bool $enabled = true,
        bool $backendUserLoggedIn = false,
        string $method = 'POST',
        string $userAgent = 'Mozilla/5.0',
        string $requestPath = '/angebote',
        bool $sameOrigin = true,
    ): TrackingContext {
        return new TrackingContext($enabled, $backendUserLoggedIn, $method, $userAgent, $requestPath, $sameOrigin);
    }
}
