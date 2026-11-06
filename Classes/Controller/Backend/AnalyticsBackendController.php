<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Controller\Backend;

use Maispace\MaiBase\Controller\Backend\AbstractBackendController;
use Maispace\MaiSeo\Analytics\Period;
use Maispace\MaiSeo\Analytics\ReportBuilder;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Site\SiteFinder;

#[AsController]
final class AnalyticsBackendController extends AbstractBackendController
{
    public function __construct(
        ModuleTemplateFactory $moduleTemplateFactory,
        IconFactory $iconFactory,
        private readonly ReportBuilder $reports,
        private readonly SiteFinder $siteFinder,
        private readonly PageRenderer $pageRenderer,
    ) {
        parent::__construct($moduleTemplateFactory, $iconFactory);
    }

    public function indexAction(): ResponseInterface
    {
        $query = $this->request->getQueryParams();
        $days = Period::normalize((int) ($query['period'] ?? 7));
        $sites = $this->siteFinder->getAllSites();
        $siteId = (string) ($query['site'] ?? '');
        if ($siteId === '' || !isset($sites[$siteId])) {
            $siteId = (string) (array_key_first($sites) ?? '');
        }

        $this->pageRenderer->addCssFile('EXT:mai_seo/Resources/Public/Css/analytics.css');
        $this->pageRenderer->addJsFooterFile('EXT:mai_seo/Resources/Public/JavaScript/analytics-charts.js');

        $moduleTemplate = $this->createModuleTemplate();
        $this->addShortcutButton($moduleTemplate, 'site_maiseo_analytics', 'Analytics');
        $this->assignMultiple($moduleTemplate, [
            'report' => $this->reports->build($siteId, $days),
            'periods' => $this->periodLinks($days, $siteId),
            'sites' => $this->siteOptions($sites, $siteId),
            'showSites' => count($sites) > 1,
            'siteId' => $siteId,
        ]);

        return $this->renderModuleResponse($moduleTemplate, 'Index');
    }

    /**
     * @return list<array{days: int, url: string, active: bool}>
     */
    private function periodLinks(int $active, string $siteId): array
    {
        $links = [];
        foreach (Period::ALLOWED as $days) {
            $links[] = [
                'days' => $days,
                'active' => $days === $active,
                'url' => (string) $this->uriBuilder->reset()->setArguments([
                    'period' => $days,
                    'site' => $siteId,
                ])->build(),
            ];
        }

        return $links;
    }

    /**
     * @param array<string, \TYPO3\CMS\Core\Site\Entity\Site> $sites
     * @return list<array{identifier: string, active: bool, url: string}>
     */
    private function siteOptions(array $sites, string $active): array
    {
        $options = [];
        foreach (array_keys($sites) as $identifier) {
            $options[] = [
                'identifier' => $identifier,
                'active' => $identifier === $active,
                'url' => (string) $this->uriBuilder->reset()->setArguments([
                    'period' => Period::normalize((int) ($this->request->getQueryParams()['period'] ?? 7)),
                    'site' => $identifier,
                ])->build(),
            ];
        }

        return $options;
    }
}
