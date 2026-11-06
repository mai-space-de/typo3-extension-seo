<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Analytics;

final readonly class TrackingContext
{
    public function __construct(
        public bool $enabled,
        public bool $backendUserLoggedIn,
        public string $method,
        public string $userAgent,
        public string $requestPath,
        public bool $sameOrigin,
    ) {}
}
