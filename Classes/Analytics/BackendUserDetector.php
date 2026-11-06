<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Analytics;

interface BackendUserDetector
{
    public function isLoggedIn(): bool;
}
