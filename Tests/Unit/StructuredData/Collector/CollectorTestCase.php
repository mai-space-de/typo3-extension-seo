<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\StructuredData\Collector;

use Maispace\MaiSeo\StructuredData\RecordStorageResolverInterface;
use PHPUnit\Framework\TestCase;

abstract class CollectorTestCase extends TestCase
{
    protected function makeStorageResolver(array $pids = [1]): RecordStorageResolverInterface
    {
        $resolver = $this->createMock(RecordStorageResolverInterface::class);
        $resolver->method('resolveStoragePids')->willReturn($pids);

        return $resolver;
    }
}
