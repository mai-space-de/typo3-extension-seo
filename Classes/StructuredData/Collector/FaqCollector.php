<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\StructuredData\Collector;

use Maispace\MaiSeo\Event\StructuredDataCollectionEvent;
use Maispace\MaiSeo\StructuredData\RecordStorageResolverInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

final class FaqCollector implements CollectorInterface
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly RecordStorageResolverInterface $recordStorageResolver,
    ) {}

    public function collect(StructuredDataCollectionEvent $event): void
    {
        if (!in_array($event->getGraph()['@type'] ?? '', $this->supportedTypes(), true)) {
            return;
        }

        $storagePids = $this->recordStorageResolver->resolveStoragePids($event->pageUid);
        if ($storagePids === []) {
            return;
        }

        $qb = $this->connectionPool->getQueryBuilderForTable('tx_maifaq_faq');
        $qb
            ->select('uid', 'question', 'answer')
            ->from('tx_maifaq_faq')
            ->where(
                $qb->expr()->in(
                    'pid',
                    $qb->createNamedParameter($storagePids, Connection::PARAM_INT_ARRAY),
                ),
            )
            ->orderBy('sorting', 'ASC');

        $this->addLanguageConstraint($qb, $this->resolveLanguageId());

        $rows = $qb->executeQuery()->fetchAllAssociative();

        if ($rows === []) {
            return;
        }

        $mainEntity = [];
        foreach ($rows as $row) {
            $question = trim((string) ($row['question'] ?? ''));
            $answer = trim((string) ($row['answer'] ?? ''));
            if ($question === '' || $answer === '') {
                continue;
            }

            $mainEntity[] = [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $answer,
                ],
            ];
        }

        if ($mainEntity === []) {
            return;
        }

        $event->addToGraph('mainEntity', $mainEntity);
    }

    public function supportedTypes(): array
    {
        return ['FAQPage'];
    }

    public function priority(): int
    {
        return 70;
    }

    private function resolveLanguageId(): int
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if (!$request instanceof ServerRequestInterface) {
            return 0;
        }

        $language = $request->getAttribute('language');

        return $language instanceof SiteLanguage ? (int) $language->getLanguageId() : 0;
    }

    /**
     * Restrict FAQ rows to the active site language.
     *
     * Default language (uid 0) includes records marked "all languages" (uid -1).
     */
    private function addLanguageConstraint(QueryBuilder $qb, int $languageId): void
    {
        if ($languageId === 0) {
            $qb->andWhere(
                $qb->expr()->in(
                    'sys_language_uid',
                    $qb->createNamedParameter([0, -1], Connection::PARAM_INT_ARRAY),
                ),
            );

            return;
        }

        $qb->andWhere(
            $qb->expr()->eq(
                'sys_language_uid',
                $qb->createNamedParameter($languageId, Connection::PARAM_INT),
            ),
        );
    }
}
