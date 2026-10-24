<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\StructuredData\Collector;

use Maispace\MaiBase\Backend\CustomPageModule\DoktypeIds;
use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;
use Maispace\MaiSeo\StructuredData\Collector\JobPostingCollector;
use PHPUnit\Framework\Attributes\Test;

final class JobPostingCollectorTest extends CollectorTestCase
{
    #[Test]
    public function priorityIsSeventyTest(): void
    {
        $collector = new JobPostingCollector();

        self::assertSame(70, $collector->priority());
    }

    #[Test]
    public function supportedTypesContainsJobPostingTest(): void
    {
        $collector = new JobPostingCollector();

        self::assertSame(['JobPosting'], $collector->supportedTypes());
    }

    #[Test]
    public function collectSkipsWhenTypeIsNotJobPostingTest(): void
    {
        $collector = new JobPostingCollector();
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: []);
        $event->addToGraph('@type', 'WebPage');

        $collector->collect($event);

        self::assertArrayNotHasKey('title', $event->getGraph());
    }

    #[Test]
    public function collectAddsTitleFromJobPageRecordTest(): void
    {
        $collector = new JobPostingCollector();
        $event = new StructuredDataCollectionEvent(pageUid: 42, pageRecord: [
            'doktype' => DoktypeIds::JOB,
            'title' => 'PHP Dev',
            'tx_maijobs_description' => 'Great job',
            'tx_maijobs_deadline' => 1750000000,
            'tx_maijobs_status' => 'open',
        ]);
        $event->addToGraph('@type', 'JobPosting');

        $collector->collect($event);

        self::assertSame('PHP Dev', $event->getGraph()['title']);
        self::assertSame('Great job', $event->getGraph()['description']);
        self::assertTrue($event->getGraph()['directApply']);
        self::assertArrayHasKey('validThrough', $event->getGraph());
    }

    #[Test]
    public function collectSkipsWhenNoJobFieldsAndWrongDoktypeTest(): void
    {
        $collector = new JobPostingCollector();
        $event = new StructuredDataCollectionEvent(pageUid: 1, pageRecord: [
            'doktype' => 1,
            'title' => 'Plain page',
        ]);
        $event->addToGraph('@type', 'JobPosting');

        $collector->collect($event);

        self::assertArrayNotHasKey('title', $event->getGraph());
    }
}
