<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Analytics;

use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;

final class DoctrineAnalyticsStore implements AnalyticsStore
{
    private const TABLE = 'tx_maiseo_analytics_hit';

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function pageExists(int $pageId): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $count = $queryBuilder
            ->count('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($pageId, ParameterType::INTEGER)),
            )
            ->executeQuery()
            ->fetchOne();

        return (int) $count > 0;
    }

    public function tokenExists(string $token): bool
    {
        $row = $this->connection()->executeQuery(
            'SELECT uid FROM ' . self::TABLE . ' WHERE hit_token = ? LIMIT 1',
            [$token],
        )->fetchOne();

        return $row !== false;
    }

    public function insert(array $row): void
    {
        $this->connection()->insert(self::TABLE, $row);
    }

    public function updateDuration(string $token, int $duration): void
    {
        $this->connection()->executeStatement(
            'UPDATE ' . self::TABLE . ' SET duration = ? WHERE hit_token = ?',
            [$duration, $token],
            [ParameterType::INTEGER, ParameterType::STRING],
        );
    }

    public function sessions(int $from, int $to, string $site, int $pageId): array
    {
        $rows = $this->connection()->executeQuery(
            'SELECT COUNT(*) AS hits,
                    COALESCE(SUM(duration), 0) AS duration,
                    SUM(CASE WHEN pid = ? THEN 1 ELSE 0 END) AS page_hits,
                    SUM(CASE WHEN pid = ? THEN duration ELSE 0 END) AS page_duration
             FROM ' . self::TABLE . '
             WHERE tstamp >= ? AND tstamp < ? AND (? = \'\' OR site = ?)
             GROUP BY visitor_hash',
            [$pageId, $pageId, $from, $to, $site, $site],
            [
                ParameterType::INTEGER,
                ParameterType::INTEGER,
                ParameterType::INTEGER,
                ParameterType::INTEGER,
                ParameterType::STRING,
                ParameterType::STRING,
            ],
        )->fetchAllAssociative();

        return array_map(static fn(array $row): array => [
            'hits' => (int) $row['hits'],
            'duration' => (int) $row['duration'],
            'page_hits' => (int) $row['page_hits'],
            'page_duration' => (int) $row['page_duration'],
        ], $rows);
    }

    public function daily(int $from, int $to, string $site, int $pageId): array
    {
        $rows = $this->connection()->executeQuery(
            'SELECT FLOOR(tstamp / 86400) AS day_index,
                    COUNT(*) AS visits,
                    COUNT(DISTINCT visitor_hash) AS visitors
             FROM ' . self::TABLE . '
             WHERE tstamp >= ? AND tstamp < ? AND (? = \'\' OR site = ?) AND (? = 0 OR pid = ?)
             GROUP BY day_index',
            [$from, $to, $site, $site, $pageId, $pageId],
            [
                ParameterType::INTEGER,
                ParameterType::INTEGER,
                ParameterType::STRING,
                ParameterType::STRING,
                ParameterType::INTEGER,
                ParameterType::INTEGER,
            ],
        )->fetchAllAssociative();

        return array_map(static fn(array $row): array => [
            'day_index' => (int) $row['day_index'],
            'visits' => (int) $row['visits'],
            'visitors' => (int) $row['visitors'],
        ], $rows);
    }

    public function topPages(int $from, int $to, string $site, int $limit): array
    {
        $limit = max(1, min(50, $limit));
        $rows = $this->connection()->executeQuery(
            'SELECT pid, path, MAX(title) AS title, COUNT(*) AS visits
             FROM ' . self::TABLE . '
             WHERE tstamp >= ? AND tstamp < ? AND (? = \'\' OR site = ?)
             GROUP BY pid, path
             ORDER BY visits DESC
             LIMIT ' . $limit,
            [$from, $to, $site, $site],
            [ParameterType::INTEGER, ParameterType::INTEGER, ParameterType::STRING, ParameterType::STRING],
        )->fetchAllAssociative();

        return array_map(static fn(array $row): array => [
            'pageId' => (int) $row['pid'],
            'path' => (string) $row['path'],
            'title' => (string) $row['title'],
            'visits' => (int) $row['visits'],
        ], $rows);
    }

    public function breakdown(string $column, int $from, int $to, string $site): array
    {
        if (!in_array($column, ['channel', 'device', 'browser', 'locale'], true)) {
            return [];
        }

        $rows = $this->connection()->executeQuery(
            'SELECT ' . $column . ' AS label, COUNT(*) AS cnt
             FROM ' . self::TABLE . '
             WHERE tstamp >= ? AND tstamp < ? AND (? = \'\' OR site = ?)
             GROUP BY ' . $column . '
             ORDER BY cnt DESC',
            [$from, $to, $site, $site],
            [ParameterType::INTEGER, ParameterType::INTEGER, ParameterType::STRING, ParameterType::STRING],
        )->fetchAllAssociative();

        return array_map(static fn(array $row): array => [
            'label' => (string) $row['label'],
            'count' => (int) $row['cnt'],
        ], $rows);
    }

    public function deleteOlderThan(int $cutoff): int
    {
        return (int) $this->connection()->executeStatement(
            'DELETE FROM ' . self::TABLE . ' WHERE tstamp < ?',
            [$cutoff],
            [ParameterType::INTEGER],
        );
    }

    private function connection(): \TYPO3\CMS\Core\Database\Connection
    {
        return $this->connectionPool->getConnectionForTable(self::TABLE);
    }
}
