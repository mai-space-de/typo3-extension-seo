<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\StructuredData;

interface RecordStorageResolverInterface
{
    /**
     * @return list<int>
     */
    public function resolveStoragePids(int $pageUid): array;
}
