<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\StructuredData;

use Maispace\MaiSeo\StructuredData\PageRoleResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;

final class PageRoleResolverTest extends TestCase
{
    #[Test]
    public function resolveAddsOrganizationAndWebSiteForHomepageTest(): void
    {
        $subject = new PageRoleResolver(
            $this->makeConnectionPool([]),
            $this->makeSiteFinder(1),
        );

        $types = $subject->resolve(1, ['uid' => 1, 'tx_maiseo_schema_type' => '']);

        self::assertContains('WebPage', $types);
        self::assertContains('Organization', $types);
        self::assertContains('WebSite', $types);
    }

    #[Test]
    public function resolveAddsPluginTypesFromContentElementsTest(): void
    {
        $subject = new PageRoleResolver(
            $this->makeConnectionPool(['maifaq_list', 'maijobs_detail']),
            $this->makeSiteFinder(1),
        );

        $types = $subject->resolve(10002, ['uid' => 10002, 'tx_maiseo_schema_type' => '']);

        self::assertContains('FAQPage', $types);
        self::assertContains('JobPosting', $types);
    }

    #[Test]
    public function resolveAddsFaqPageForMaispaceFaqListCtypeTest(): void
    {
        $subject = new PageRoleResolver(
            $this->makeConnectionPool(['maispace_faq_list']),
            $this->makeSiteFinder(1),
        );

        $types = $subject->resolve(19, ['uid' => 19, 'tx_maiseo_schema_type' => '']);

        self::assertContains('FAQPage', $types);
        self::assertContains('WebPage', $types);
    }

    #[Test]
    public function resolveAddsCtypeSpecificTypesTest(): void
    {
        $subject = new PageRoleResolver(
            $this->makeConnectionPool(['maispace_map', 'maispace_faq']),
            $this->makeSiteFinder(1),
        );

        $types = $subject->resolve(10001, ['uid' => 10001, 'tx_maiseo_schema_type' => '']);

        self::assertContains('FAQPage', $types);
        self::assertContains('Place', $types);
        self::assertContains('LocalBusiness', $types);
    }

    /**
     * @param list<string> $cTypes
     */
    private function makeConnectionPool(array $cTypes): ConnectionPool
    {
        $rows = array_map(static fn(string $cType): array => ['CType' => $cType], $cTypes);

        $result = $this->createMock(\Doctrine\DBAL\Result::class);
        $result->method('fetchAllAssociative')->willReturn($rows);

        $exprBuilder = $this->createMock(ExpressionBuilder::class);
        $exprBuilder->method('eq')->willReturn('1=1');

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

    private function makeSiteFinder(int $rootPageId): SiteFinder
    {
        $site = $this->createMock(Site::class);
        $site->method('getRootPageId')->willReturn($rootPageId);

        $finder = $this->createMock(SiteFinder::class);
        $finder->method('getSiteByPageId')->willReturn($site);

        return $finder;
    }
}
