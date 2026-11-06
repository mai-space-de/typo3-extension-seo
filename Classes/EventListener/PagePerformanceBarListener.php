<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\EventListener;

use Maispace\MaiSeo\Analytics\PagePerformanceMarkup;
use Maispace\MaiSeo\Analytics\Period;
use Maispace\MaiSeo\Analytics\ReportBuilder;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Site\SiteFinder;

#[AsEventListener(identifier: 'maiseo/page-performance-bar')]
final class PagePerformanceBarListener
{
    public function __construct(
        private readonly ReportBuilder $reports,
        private readonly PagePerformanceMarkup $markup,
        private readonly SiteFinder $siteFinder,
        private readonly UriBuilder $uriBuilder,
        private readonly PageRenderer $pageRenderer,
    ) {}

    public function __invoke(ModifyPageLayoutContentEvent $event): void
    {
        $request = $event->getRequest();
        $query = $request->getQueryParams();
        $pageId = (int) ($query['id'] ?? 0);
        if ($pageId <= 0 || (int) ($query['viewMode'] ?? 0) === 2) {
            return;
        }

        try {
            $site = $this->siteFinder->getSiteByPageId($pageId);
        } catch (SiteNotFoundException) {
            return;
        }

        if (!(bool) $site->getSettings()->get('seo.analytics.enabled', true)) {
            return;
        }

        $days = Period::normalize((int) ($query['tx_maiseo_period'] ?? 7));
        $this->pageRenderer->addCssFile('EXT:mai_seo/Resources/Public/Css/analytics.css');
        $event->addHeaderContent($this->markup->render(
            $this->reports->build($site->getIdentifier(), $days, $pageId),
            $this->periodLinks($request, $days),
            (string) $this->uriBuilder->buildUriFromRoute('site_maiseo_analytics', [
                'period' => $days,
                'site' => $site->getIdentifier(),
            ]),
        ));
    }

    /**
     * @return list<array{days: int, url: string, active: bool}>
     */
    private function periodLinks(ServerRequestInterface $request, int $active): array
    {
        $links = [];
        foreach (Period::ALLOWED as $days) {
            $query = $request->getQueryParams();
            $query['tx_maiseo_period'] = $days;
            $links[] = [
                'days' => $days,
                'active' => $days === $active,
                'url' => (string) $request->getUri()->withQuery(http_build_query($query)),
            ];
        }

        return $links;
    }
}
