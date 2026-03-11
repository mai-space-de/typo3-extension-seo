<?php

declare(strict_types = 1);

namespace Maispace\MaispacesSeo\ViewHelpers\Seo;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

abstract class AbstractSeoViewHelper extends AbstractViewHelper
{
    /**
     * Resolve the page record for the given UID, or the current page when 0.
     *
     * @return array<string, mixed>
     */
    protected function resolvePageRecord(int $pageUid): array
    {
        if ($pageUid > 0) {
            $tsfe = $GLOBALS['TSFE'] ?? null;
            if ($tsfe instanceof TypoScriptFrontendController) {
                /** @var array<string, mixed> $page */
                $page = $tsfe->sys_page->getPage($pageUid) ?: [];

                return $page;
            }

            return [];
        }

        $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
        $frontendController = $request->getAttribute('frontend.controller');
        if ($frontendController instanceof TypoScriptFrontendController) {
            /** @var array<string, mixed> $page */
            $page = $frontendController->page ?? [];

            return $page;
        }

        $tsfe = $GLOBALS['TSFE'] ?? null;
        if ($tsfe instanceof TypoScriptFrontendController) {
            /** @var array<string, mixed> $page */
            $page = $tsfe->page ?? [];

            return $page;
        }

        return [];
    }

    /**
     * Resolve the plugin.tx_maispace_seo TypoScript settings from the request.
     *
     * @return array<string, mixed>
     */
    protected function resolveTypoScriptSettings(): array
    {
        $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
        $typoscript = $request->getAttribute('frontend.typoscript');
        if ($typoscript instanceof FrontendTypoScript) {
            $setup = $typoscript->getSetupArray();
            $pluginSetup = is_array($setup['plugin.'] ?? null) ? $setup['plugin.'] : [];
            /** @var array<string, mixed> $seoSettings */
            $seoSettings = is_array($pluginSetup['tx_maispace_seo.'] ?? null) ? $pluginSetup['tx_maispace_seo.'] : [];

            return $seoSettings;
        }

        return [];
    }
}
