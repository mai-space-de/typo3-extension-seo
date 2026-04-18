<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\EventListener;

use Maispace\MaiSeo\Event\PageRecordSavedEvent;
use Maispace\MaiSeo\StructuredData\StructuredDataService;

final class PageRecordSavedListener
{
    public function __construct(
        private readonly StructuredDataService $structuredDataService,
    ) {}

    public function __invoke(PageRecordSavedEvent $event): void
    {
        $this->structuredDataService->invalidate($event->pageUid);
    }
}
