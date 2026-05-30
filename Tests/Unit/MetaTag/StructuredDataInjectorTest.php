<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Tests\Unit\MetaTag;

use Maispace\MaiSeo\MetaTag\StructuredDataInjector;
use Maispace\MaiSeo\StructuredData\StructuredDataProviderInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Frontend\Page\PageInformation;

final class StructuredDataInjectorTest extends TestCase
{
    #[Test]
    public function generateAddsJsonLdScriptToPageRendererTest(): void
    {
        $graph = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => 'Home',
        ];

        $service = $this->createMock(StructuredDataProviderInterface::class);
        $service->method('getForPage')->with(1)->willReturn($graph);

        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer->expects(self::once())
            ->method('addHeaderData')
            ->with(self::stringContains('application/ld+json'));

        $pageInformation = new PageInformation();
        $pageInformation->setId(1);

        $request = (new ServerRequest())
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.typoscript', [
                'plugin.' => [
                    'tx_maiseo.' => [
                        'settings.' => [
                            'structuredData.' => [
                                'injectViaPageRenderer' => 1,
                            ],
                        ],
                    ],
                ],
            ]);

        $subject = new StructuredDataInjector($service, $pageRenderer);
        $subject->generate(['request' => $request]);
    }

    #[Test]
    public function generateSkipsWhenDisabledInTypoScriptTest(): void
    {
        $service = $this->createMock(StructuredDataProviderInterface::class);
        $service->expects(self::never())->method('getForPage');

        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer->expects(self::never())->method('addHeaderData');

        $pageInformation = new PageInformation();
        $pageInformation->setId(1);

        $request = (new ServerRequest())
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.typoscript', [
                'plugin.' => [
                    'tx_maiseo.' => [
                        'settings.' => [
                            'structuredData.' => [
                                'injectViaPageRenderer' => 0,
                            ],
                        ],
                    ],
                ],
            ]);

        $subject = new StructuredDataInjector($service, $pageRenderer);
        $subject->generate(['request' => $request]);
    }
}
