<?php

declare(strict_types=1);

namespace Maispace\MaispacesSeo\Controller\Backend;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

class SeoController
{
    private const EXT_KEY = 'maispace_seo';

    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly ConnectionPool $connectionPool,
        private readonly UriBuilder $uriBuilder,
    ) {}

    public function indexAction(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($request);

        $qb = $this->connectionPool->getQueryBuilderForTable('pages');
        $rows = $qb->select(
            'uid',
            'title',
            'tx_maispace_seo_jsonld_type',
            'tx_maispace_seo_jsonld_name',
            'tx_maispace_seo_og_title',
            'tx_maispace_seo_og_description',
            'tx_maispace_seo_og_image'
        )
            ->from('pages')
            ->where(
                $qb->expr()->eq('deleted', $qb->createNamedParameter(0, \PDO::PARAM_INT)),
                $qb->expr()->eq('hidden', $qb->createNamedParameter(0, \PDO::PARAM_INT)),
                $qb->expr()->eq('sys_language_uid', $qb->createNamedParameter(0, \PDO::PARAM_INT))
            )
            ->orderBy('sorting')
            ->executeQuery()
            ->fetchAllAssociative();

        $totalPages = count($rows);
        $pagesWithOgImage = 0;
        $pagesMissingTitle = 0;
        $pagesMissingOgImage = 0;

        foreach ($rows as $row) {
            if ((int)$row['tx_maispace_seo_og_image'] > 0) {
                ++$pagesWithOgImage;
            } else {
                ++$pagesMissingOgImage;
            }
            $ogTitle = (string)($row['tx_maispace_seo_og_title'] ?? '');
            $pageTitle = (string)($row['title'] ?? '');
            if ($ogTitle === '' && $pageTitle === '') {
                ++$pagesMissingTitle;
            }
        }

        $view = $this->createView('Backend/Index');
        $view->assignMultiple([
            'pages'             => $rows,
            'totalPages'        => $totalPages,
            'pagesWithOgImage'  => $pagesWithOgImage,
            'pagesMissingTitle' => $pagesMissingTitle,
            'pagesMissingOgImage' => $pagesMissingOgImage,
            'statisticsUri'     => (string)$this->uriBuilder->buildUriFromRoute('maispace_seo.statistics'),
        ]);

        $moduleTemplate->setContent($view->render());

        return $moduleTemplate->renderResponse('Backend/Index');
    }

    public function statisticsAction(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($request);

        // Schema type distribution
        $qb = $this->connectionPool->getQueryBuilderForTable('pages');
        $typeRows = $qb->select('tx_maispace_seo_jsonld_type')
            ->addSelectLiteral('COUNT(*) AS page_count')
            ->from('pages')
            ->where(
                $qb->expr()->eq('deleted', $qb->createNamedParameter(0, \PDO::PARAM_INT)),
                $qb->expr()->eq('hidden', $qb->createNamedParameter(0, \PDO::PARAM_INT)),
                $qb->expr()->eq('sys_language_uid', $qb->createNamedParameter(0, \PDO::PARAM_INT))
            )
            ->groupBy('tx_maispace_seo_jsonld_type')
            ->orderBy('page_count', 'DESC')
            ->executeQuery()
            ->fetchAllAssociative();

        $totalPages = array_sum(array_column($typeRows, 'page_count'));

        $typeStats = [];
        foreach ($typeRows as $row) {
            $count = (int)$row['page_count'];
            $typeStats[] = [
                'type'       => $row['tx_maispace_seo_jsonld_type'] !== '' ? $row['tx_maispace_seo_jsonld_type'] : '(none)',
                'count'      => $count,
                'percentage' => $totalPages > 0 ? round(($count / $totalPages) * 100, 1) : 0,
            ];
        }

        // OG image coverage
        $qb2 = $this->connectionPool->getQueryBuilderForTable('pages');
        $withImage = (int)$qb2->count('uid')
            ->from('pages')
            ->where(
                $qb2->expr()->eq('deleted', $qb2->createNamedParameter(0, \PDO::PARAM_INT)),
                $qb2->expr()->eq('hidden', $qb2->createNamedParameter(0, \PDO::PARAM_INT)),
                $qb2->expr()->eq('sys_language_uid', $qb2->createNamedParameter(0, \PDO::PARAM_INT)),
                $qb2->expr()->gt('tx_maispace_seo_og_image', $qb2->createNamedParameter(0, \PDO::PARAM_INT))
            )
            ->executeQuery()
            ->fetchOne();

        $withoutImage = $totalPages - $withImage;
        $imagePercentage = $totalPages > 0 ? round(($withImage / $totalPages) * 100, 1) : 0;

        $view = $this->createView('Backend/Statistics');
        $view->assignMultiple([
            'typeStats'       => $typeStats,
            'totalPages'      => $totalPages,
            'withImage'       => $withImage,
            'withoutImage'    => $withoutImage,
            'imagePercentage' => $imagePercentage,
            'indexUri'        => (string)$this->uriBuilder->buildUriFromRoute('maispace_seo'),
        ]);

        $moduleTemplate->setContent($view->render());

        return $moduleTemplate->renderResponse('Backend/Statistics');
    }

    private function createView(string $template): StandaloneView
    {
        $extPath = ExtensionManagementUtility::extPath(self::EXT_KEY);

        $view = new StandaloneView();
        $view->setTemplateRootPaths([$extPath . 'Resources/Private/Templates/']);
        $view->setPartialRootPaths([$extPath . 'Resources/Private/Partials/']);
        $view->setLayoutRootPaths([$extPath . 'Resources/Private/Layouts/']);
        $view->setTemplate($template);

        return $view;
    }
}
