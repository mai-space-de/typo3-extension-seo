<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\StructuredData;

use Maispace\MaiBase\Backend\CustomPageModule\DoktypeIds;
use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;
use Maispace\MaiSeo\StructuredData\AutoGenerator;
use Maispace\MaiSeo\StructuredData\Collector\CollectorInterface;
use Maispace\MaiSeo\StructuredData\Collector\CollectorRegistry;
use Maispace\MaiSeo\StructuredData\PageRoleResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;

final class AutoGeneratorTest extends TestCase
{
    #[Test]
    public function newsPageYieldsOneNewsArticleNodeAndNoArticleNodeTest(): void
    {
        $graph = $this->generate(['doktype' => DoktypeIds::NEWS]);

        $types = array_column($graph['@graph'], '@type');
        self::assertSame(['WebPage', 'NewsArticle'], $types);
    }

    #[Test]
    public function breadcrumbStaysOnThePageNodeOnlyTest(): void
    {
        $graph = $this->generate(['doktype' => DoktypeIds::NEWS]);

        self::assertArrayHasKey('breadcrumb', $graph['@graph'][0]);
        self::assertArrayNotHasKey('breadcrumb', $graph['@graph'][1]);
    }

    /**
     * @param array<string, mixed> $pageRecord
     *
     * @return array<string, mixed>
     */
    private function generate(array $pageRecord): array
    {
        $pageRecord += ['uid' => 42, 'tx_maiseo_schema_type' => ''];

        $result = $this->createMock(\Doctrine\DBAL\Result::class);
        $result->method('fetchAssociative')->willReturn($pageRecord);
        $result->method('fetchAllAssociative')->willReturn([]);

        $exprBuilder = $this->createMock(ExpressionBuilder::class);
        $exprBuilder->method('eq')->willReturn('1=1');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('expr')->willReturn($exprBuilder);
        $queryBuilder->method('createNamedParameter')->willReturn(':p1');
        $queryBuilder->method('executeQuery')->willReturn($result);

        $pool = $this->createMock(ConnectionPool::class);
        $pool->method('getQueryBuilderForTable')->willReturn($queryBuilder);

        $site = $this->createMock(Site::class);
        $site->method('getRootPageId')->willReturn(1);
        $siteFinder = $this->createMock(SiteFinder::class);
        $siteFinder->method('getSiteByPageId')->willReturn($site);

        $cacheManager = $this->createMock(CacheManager::class);
        $cacheManager->method('getCache')->willReturn($this->createMock(FrontendInterface::class));

        $collector = new class implements CollectorInterface {
            public function collect(StructuredDataCollectionEvent $event): void
            {
                $event->addToGraph('name', 'Seite');
                $event->addToGraph('breadcrumb', ['@type' => 'BreadcrumbList']);
            }

            public function supportedTypes(): array
            {
                return ['*'];
            }

            public function priority(): int
            {
                return 100;
            }
        };

        $generator = new AutoGenerator(
            new CollectorRegistry([$collector]),
            $this->createMock(EventDispatcherInterface::class),
            $cacheManager,
            $pool,
            new PageRoleResolver($pool, $siteFinder),
        );

        return $generator->generateForPage(42, false);
    }
}
