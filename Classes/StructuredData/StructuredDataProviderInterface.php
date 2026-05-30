<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\StructuredData;

interface StructuredDataProviderInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getForPage(int $pageUid): array;
}
