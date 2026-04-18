<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Hook;

use Maispace\MaiSeo\Event\PageRecordSavedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\DataHandling\DataHandler;

final class PageRecordSaveHook
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function processDatamap_afterDatabaseOperations(
        string $status,
        string $table,
        int|string $id,
        array $fieldArray,
        DataHandler $dataHandler,
    ): void {
        if ($table !== 'pages') {
            return;
        }

        $pageUid = $status === 'new'
            ? (int)($dataHandler->substNEWwithIDs[$id] ?? 0)
            : (int)$id;

        if ($pageUid === 0) {
            return;
        }

        $this->eventDispatcher->dispatch(new PageRecordSavedEvent($pageUid, $fieldArray, $status));
    }
}
