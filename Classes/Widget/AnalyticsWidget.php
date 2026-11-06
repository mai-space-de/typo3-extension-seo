<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Widget;

use Maispace\MaiSeo\Analytics\Period;
use Maispace\MaiSeo\Analytics\ReportBuilder;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Dashboard\Widgets\AdditionalCssInterface;
use TYPO3\CMS\Dashboard\Widgets\RequestAwareWidgetInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;

final class AnalyticsWidget implements WidgetInterface, RequestAwareWidgetInterface, AdditionalCssInterface
{
    private ServerRequestInterface $request;

    /**
     * @param array{section?: string, days?: int} $options
     */
    public function __construct(
        private readonly WidgetConfigurationInterface $configuration,
        private readonly ReportBuilder $reports,
        private readonly BackendViewFactory $backendViewFactory,
        private readonly array $options = [],
    ) {}

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    public function renderWidgetContent(): string
    {
        $days = Period::normalize((int) ($this->options['days'] ?? 7));
        $view = $this->backendViewFactory->create($this->request, ['maispace/mai-seo']);
        $view->assignMultiple([
            'report' => $this->reports->build('', $days),
            'section' => (string) ($this->options['section'] ?? 'performance'),
            'configuration' => $this->configuration,
        ]);

        return $view->render('Widget/AnalyticsWidget');
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getCssFiles(): array
    {
        return ['EXT:mai_seo/Resources/Public/Css/analytics.css'];
    }
}
