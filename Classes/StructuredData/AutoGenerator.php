<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\StructuredData;

use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;
use Maispace\MaiSeo\Event\StructuredDataRenderEvent;
use Maispace\MaiSeo\StructuredData\Collector\CollectorRegistry;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\SingletonInterface;

final class AutoGenerator implements SingletonInterface
{
    public function __construct(
        private readonly CollectorRegistry $collectorRegistry,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly CacheManager $cacheManager,
        private readonly ConnectionPool $connectionPool,
        private readonly PageRoleResolver $pageRoleResolver,
    ) {}

    public function generateForPage(int $pageUid, bool $useCache = true): array
    {
        $cache = $this->cacheManager->getCache('maiseo_structured_data');
        $cacheKey = 'auto_' . $pageUid;

        if ($useCache && $cache->has($cacheKey)) {
            return (array) $cache->get($cacheKey);
        }

        $pageRecord = $this->fetchPageRecord($pageUid);
        if ($pageRecord === null) {
            return [];
        }

        $schemaTypes = $this->pageRoleResolver->resolve($pageUid, $pageRecord);
        if ($schemaTypes === []) {
            $schemaTypes = ['WebPage'];
        }

        $nodes = [];
        foreach ($schemaTypes as $schemaType) {
            $collectionEvent = new StructuredDataCollectionEvent($pageUid, $pageRecord);
            $collectionEvent->setRootType($schemaType);

            foreach ($this->collectorRegistry->getCollectors() as $collector) {
                $collector->collect($collectionEvent);
            }

            $node = $collectionEvent->getGraph();
            if ($node === [] || !isset($node['@type'])) {
                continue;
            }

            $nodes[] = $node;
        }

        if ($nodes === []) {
            return [];
        }

        if (count($nodes) === 1) {
            $graph = $nodes[0];
        } else {
            $graph = ['@graph' => $nodes];
        }

        $graph['@context'] = 'https://schema.org';

        $renderEvent = new StructuredDataRenderEvent($pageUid, $graph);
        $this->eventDispatcher->dispatch($renderEvent);
        $graph = $renderEvent->getGraph();

        if ($useCache) {
            $cache->set($cacheKey, $graph, ['pageId_' . $pageUid], 86400);
        }

        return $graph;
    }

    public function invalidateCache(int $pageUid): void
    {
        $cache = $this->cacheManager->getCache('maiseo_structured_data');
        $cache->remove('auto_' . $pageUid);
    }

    private function fetchPageRecord(int $pageUid): ?array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $row = $queryBuilder
            ->select('*')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($pageUid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative();

        return $row === false ? null : (array) $row;
    }
}
