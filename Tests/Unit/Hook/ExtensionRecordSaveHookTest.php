<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\Hook;

use Doctrine\DBAL\Result;
use Maispace\MaiSeo\Event\PageRecordSavedEvent;
use Maispace\MaiSeo\Hook\ExtensionRecordSaveHook;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\DataHandling\DataHandler;

final class ExtensionRecordSaveHookTest extends TestCase
{
    private EventDispatcherInterface&MockObject $dispatcher;
    private ConnectionPool&MockObject $connectionPool;
    private DataHandler&MockObject $dataHandler;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->connectionPool = $this->createMock(ConnectionPool::class);
        $this->dataHandler = $this->createMock(DataHandler::class);
    }

    private function makeHook(): ExtensionRecordSaveHook
    {
        return new ExtensionRecordSaveHook($this->dispatcher, $this->connectionPool);
    }

    private function makePoolWithPid(int $pid): ConnectionPool
    {
        $exprBuilder = $this->createMock(ExpressionBuilder::class);
        $exprBuilder->method('eq')->willReturn('1=1');

        $result = $this->createMock(Result::class);
        $result->method('fetchAssociative')->willReturn(['pid' => $pid]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('expr')->willReturn($exprBuilder);
        $qb->method('createNamedParameter')->willReturn(':p1');
        $qb->method('executeQuery')->willReturn($result);

        $pool = $this->createMock(ConnectionPool::class);
        $pool->method('getQueryBuilderForTable')->willReturn($qb);

        return $pool;
    }

    private function makePoolWithNoPid(): ConnectionPool
    {
        $exprBuilder = $this->createMock(ExpressionBuilder::class);
        $exprBuilder->method('eq')->willReturn('1=1');

        $result = $this->createMock(Result::class);
        $result->method('fetchAssociative')->willReturn(false);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('expr')->willReturn($exprBuilder);
        $qb->method('createNamedParameter')->willReturn(':p1');
        $qb->method('executeQuery')->willReturn($result);

        $pool = $this->createMock(ConnectionPool::class);
        $pool->method('getQueryBuilderForTable')->willReturn($qb);

        return $pool;
    }

    // ---------------------------------------------------------------------------
    // Ignored tables
    // ---------------------------------------------------------------------------

    #[Test]
    public function ignoredTableDispatchesNoEventTest(): void
    {
        $this->dispatcher->expects(self::never())->method('dispatch');

        $this->makeHook()->processDatamap_afterDatabaseOperations(
            'update',
            'pages',
            42,
            [],
            $this->dataHandler,
        );
    }

    #[Test]
    public function unknownTableDispatchesNoEventTest(): void
    {
        $this->dispatcher->expects(self::never())->method('dispatch');

        $this->makeHook()->processDatamap_afterDatabaseOperations(
            'update',
            'tt_content',
            1,
            [],
            $this->dataHandler,
        );
    }

    // ---------------------------------------------------------------------------
    // New records — pid from fieldArray
    // ---------------------------------------------------------------------------

    /** @return array<string, array{string}> */
    public static function watchedTablesProvider(): array
    {
        return [
            'news' => ['tx_mainews_news'],
            'jobs' => ['tx_maijobs_job'],
            'team' => ['tx_maiteam_team_member'],
            'locations' => ['tx_mailocations_location'],
        ];
    }

    #[Test]
    #[DataProvider('watchedTablesProvider')]
    public function newRecordDispatchesEventWithPidFromFieldArrayTest(string $table): void
    {
        $this->dispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(static function (PageRecordSavedEvent $event): bool {
                return $event->pageUid === 7 && $event->status === 'new';
            }));

        $hook = new ExtensionRecordSaveHook($this->dispatcher, $this->connectionPool);
        $hook->processDatamap_afterDatabaseOperations(
            'new',
            $table,
            'NEW12345',
            ['pid' => 7, 'title' => 'Test'],
            $this->dataHandler,
        );
    }

    #[Test]
    public function newRecordWithMissingPidDispatchesNoEventTest(): void
    {
        $this->dispatcher->expects(self::never())->method('dispatch');

        $this->makeHook()->processDatamap_afterDatabaseOperations(
            'new',
            'tx_mainews_news',
            'NEW99',
            [],
            $this->dataHandler,
        );
    }

    // ---------------------------------------------------------------------------
    // Updated records — pid from fieldArray (record was moved)
    // ---------------------------------------------------------------------------

    #[Test]
    public function updateWithPidInFieldArrayDispatchesEventTest(): void
    {
        $this->dispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(static function (PageRecordSavedEvent $event): bool {
                return $event->pageUid === 15 && $event->status === 'update';
            }));

        $this->makeHook()->processDatamap_afterDatabaseOperations(
            'update',
            'tx_maijobs_job',
            42,
            ['pid' => 15, 'title' => 'New title'],
            $this->dataHandler,
        );
    }

    // ---------------------------------------------------------------------------
    // Updated records — pid resolved via DB lookup
    // ---------------------------------------------------------------------------

    #[Test]
    public function updateWithoutPidInFieldArrayFetchesPidFromDatabaseTest(): void
    {
        $this->dispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(static function (PageRecordSavedEvent $event): bool {
                return $event->pageUid === 33;
            }));

        $hook = new ExtensionRecordSaveHook($this->dispatcher, $this->makePoolWithPid(33));
        $hook->processDatamap_afterDatabaseOperations(
            'update',
            'tx_maiteam_team_member',
            99,
            ['first_name' => 'Updated'],
            $this->dataHandler,
        );
    }

    #[Test]
    public function updateWithUnresolvablePidDispatchesNoEventTest(): void
    {
        $this->dispatcher->expects(self::never())->method('dispatch');

        $hook = new ExtensionRecordSaveHook($this->dispatcher, $this->makePoolWithNoPid());
        $hook->processDatamap_afterDatabaseOperations(
            'update',
            'tx_mailocations_location',
            55,
            [],
            $this->dataHandler,
        );
    }

    #[Test]
    public function updateWithZeroIdAndNoPidInFieldArrayDispatchesNoEventTest(): void
    {
        $this->dispatcher->expects(self::never())->method('dispatch');

        $this->makeHook()->processDatamap_afterDatabaseOperations(
            'update',
            'tx_mainews_news',
            0,
            [],
            $this->dataHandler,
        );
    }
}
