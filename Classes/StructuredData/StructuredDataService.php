<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\StructuredData;

use Maispace\MaiSeo\Utility\JsonMerge;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\SingletonInterface;

final class StructuredDataService implements SingletonInterface, StructuredDataProviderInterface
{
    public function __construct(
        private readonly AutoGenerator $autoGenerator,
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function getForPage(int $pageUid): array
    {
        $autoData = $this->autoGenerator->generateForPage($pageUid);
        $editorData = $this->loadEditorOverrides($pageUid);

        if (empty($editorData)) {
            return $autoData;
        }

        return JsonMerge::deepMerge($autoData, $editorData);
    }

    public function getAutoDataForPage(int $pageUid, bool $useCache = true): array
    {
        return $this->autoGenerator->generateForPage($pageUid, $useCache);
    }

    public function saveEditorOverrides(int $pageUid, array $overrides): void
    {
        $json = $overrides === [] ? '' : json_encode($overrides, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->connectionPool
            ->getConnectionForTable('pages')
            ->update('pages', ['tx_maiseo_structured_data' => $json], ['uid' => $pageUid]);
    }

    public function invalidate(int $pageUid): void
    {
        $this->autoGenerator->invalidateCache($pageUid);
    }

    private function loadEditorOverrides(int $pageUid): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $row = $queryBuilder
            ->select('tx_maiseo_structured_data')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($pageUid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative();

        if ($row === false || empty($row['tx_maiseo_structured_data'])) {
            return [];
        }

        $decoded = json_decode((string) $row['tx_maiseo_structured_data'], true);
        return is_array($decoded) ? $decoded : [];
    }
}
