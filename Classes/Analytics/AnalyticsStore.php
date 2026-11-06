<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Analytics;

interface AnalyticsStore
{
    public function pageExists(int $pageId): bool;

    public function tokenExists(string $token): bool;

    /**
     * @param array<string, int|string> $row
     */
    public function insert(array $row): void;

    public function updateDuration(string $token, int $duration): void;

    /**
     * @return list<array{hits: int, duration: int, page_hits: int, page_duration: int}>
     */
    public function sessions(int $from, int $to, string $site, int $pageId): array;

    /**
     * @return list<array{day_index: int, visits: int, visitors: int}>
     */
    public function daily(int $from, int $to, string $site, int $pageId): array;

    /**
     * @return list<array{pageId: int, path: string, title: string, visits: int}>
     */
    public function topPages(int $from, int $to, string $site, int $limit): array;

    /**
     * @return list<array{label: string, count: int}>
     */
    public function breakdown(string $column, int $from, int $to, string $site): array;

    public function deleteOlderThan(int $cutoff): int;
}
