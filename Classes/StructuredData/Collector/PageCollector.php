<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\StructuredData\Collector;

use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

final class PageCollector implements CollectorInterface
{
    public function collect(StructuredDataCollectionEvent $event): void
    {
        $record = $event->pageRecord;

        if (!empty($record['title'])) {
            $event->addToGraph('name', $record['title']);
        }

        if (!empty($record['description'])) {
            $event->addToGraph('description', $record['description']);
        }

        if (!empty($record['canonical_link'])) {
            $event->addToGraph('url', $record['canonical_link']);
        } else {
            $url = $this->resolvePageUrl($event->pageUid, $record);
            if ($url !== '') {
                $event->addToGraph('url', $url);
            }
        }

        if (!empty($record['crdate'])) {
            $event->addToGraph('datePublished', date('c', (int) $record['crdate']));
        }

        if (!empty($record['tstamp'])) {
            $event->addToGraph('dateModified', date('c', (int) $record['tstamp']));
        }

        if (empty($event->getGraph()['@type'])) {
            $schemaType = trim((string) ($record['tx_maiseo_schema_type'] ?? ''));
            $event->setRootType($schemaType !== '' ? $schemaType : 'WebPage');
        }
    }

    public function supportedTypes(): array
    {
        return ['*'];
    }

    public function priority(): int
    {
        return 100;
    }

    private function resolvePageUrl(int $pageUid, array $pageRecord): string
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if (!$request instanceof ServerRequestInterface) {
            return '';
        }

        $site = $request->getAttribute('site');
        if ($site === null) {
            return '';
        }

        $language = $request->getAttribute('language');
        if ($language === null) {
            return '';
        }

        $path = $this->getPagePath($pageUid, $request);
        if ($path === '') {
            return '';
        }

        $uri = $this->joinBaseAndPath($language->getBase(), $path);

        // PageArguments::getPageType() returns a string; cast before comparing.
        $pageType = (int) ($request->getAttribute('routing')?->getPageType() ?? 0);
        if ($pageType !== 0) {
            $uri = $uri->withQuery(http_build_query(['type' => $pageType]));
        }

        return (string) $uri;
    }

    private function joinBaseAndPath(UriInterface $base, string $path): UriInterface
    {
        $basePath = rtrim($base->getPath(), '/');
        $pagePath = ($path === '/' || $path === '') ? '' : '/' . ltrim($path, '/');
        $combined = ($basePath . $pagePath) !== '' ? $basePath . $pagePath : '/';

        return $base->withPath($combined)->withQuery('')->withFragment('');
    }

    private function getPagePath(int $pageUid, ServerRequestInterface $request): string
    {
        $pageInformation = $request->getAttribute('frontend.page.information');
        if ($pageInformation === null) {
            return '';
        }

        $slug = $pageInformation->getPageRecord()['slug'] ?? '';
        if ($slug === '' || $slug === '/') {
            return '/';
        }

        return $slug;
    }
}
