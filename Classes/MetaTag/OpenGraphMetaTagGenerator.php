<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\MetaTag;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Resource\FileCollector;

/**
 * Emits Open Graph and Twitter Card meta tags from mai_seo page fields.
 */
#[Autoconfigure(public: true)]
final readonly class OpenGraphMetaTagGenerator
{
    public function __construct(
        private MetaTagManagerRegistry $metaTagManagerRegistry,
        private ImageService $imageService,
        private OpenGraphValueResolver $valueResolver,
    ) {}

    /**
     * @param array{request: ServerRequestInterface} $params
     */
    public function generate(array $params): void
    {
        /** @var ServerRequestInterface $request */
        $request = $params['request'];
        $pageRecord = $request->getAttribute('frontend.page.information')->getPageRecord();
        $site = $request->getAttribute('site');
        $siteLanguage = $request->getAttribute('language');
        $siteName = $this->resolveSiteName($siteLanguage, $site->getConfiguration()['websiteTitle'] ?? '');

        $title = $this->valueResolver->resolveTitle($pageRecord, $siteName);
        $description = $this->valueResolver->resolveDescription($pageRecord);
        $ogType = $this->valueResolver->resolveOpenGraphType($pageRecord);
        $pageUrl = $this->resolvePageUrl($request, $pageRecord);
        $images = $this->resolveImages($pageRecord);

        if ($title !== '') {
            $this->addProperty('og:title', $title);
            $this->addProperty('twitter:title', $title);
        }

        if ($description !== '') {
            $this->addProperty('og:description', $description);
            $this->addProperty('twitter:description', $description);
        }

        foreach ($images as $image) {
            $subProperties = [
                'url' => $image['url'],
                'width' => $image['width'],
                'height' => $image['height'],
            ];
            if ($image['alternative'] !== '') {
                $subProperties['alt'] = $image['alternative'];
            }

            $this->addProperty('og:image', $image['url'], $subProperties);

            $twitterImageProperties = [];
            if ($image['alternative'] !== '') {
                $twitterImageProperties['alt'] = $image['alternative'];
            }
            $this->addProperty('twitter:image', $image['url'], $twitterImageProperties);
        }

        $this->addProperty('og:type', $ogType);

        if ($siteName !== '') {
            $this->addProperty('og:site_name', $siteName);
        }

        if ($pageUrl !== '') {
            $this->addProperty('og:url', $pageUrl);
        }

        $this->addProperty(
            'twitter:card',
            $this->valueResolver->resolveTwitterCard($images !== []),
        );
    }

    /**
     * @param array<string, mixed> $pageRecord
     *
     * @return list<array{url: string, width: float, height: float, alternative: string}>
     */
    private function resolveImages(array $pageRecord): array
    {
        $images = [];

        foreach ($this->valueResolver->resolveImageFieldCandidates($pageRecord) as $fieldName) {
            $fileCollector = GeneralUtility::makeInstance(FileCollector::class);
            $fileCollector->addFilesFromRelation('pages', $fieldName, $pageRecord);

            foreach ($this->generateSocialImages($fileCollector->getFiles()) as $image) {
                $images[] = $image;
            }

            if ($images !== []) {
                break;
            }
        }

        return $images;
    }

    /**
     * @param list<FileReference> $fileReferences
     *
     * @return list<array{url: string, width: float, height: float, alternative: string}>
     */
    private function generateSocialImages(array $fileReferences): array
    {
        $socialImages = [];

        foreach ($fileReferences as $fileReference) {
            $arguments = $fileReference->getProperties();
            $image = $this->processSocialImage($fileReference);
            $socialImages[] = [
                'url' => $this->imageService->getImageUri($image, true),
                'width' => floor((float) $image->getProperty('width')),
                'height' => floor((float) $image->getProperty('height')),
                'alternative' => (string) ($arguments['alternative'] ?? ''),
            ];
        }

        return $socialImages;
    }

    private function processSocialImage(FileReference $fileReference): FileInterface
    {
        $arguments = $fileReference->getProperties();
        $cropVariantCollection = CropVariantCollection::create((string) ($arguments['crop'] ?? ''));
        $cropVariantName = ($arguments['cropVariant'] ?? false) ?: 'social';
        $cropArea = $cropVariantCollection->getCropArea($cropVariantName);
        $crop = $cropArea->makeAbsoluteBasedOnFile($fileReference);

        $processingConfiguration = [
            'crop' => $crop,
            'maxWidth' => 2000,
        ];

        $needsProcessing = $fileReference->getProperty('width') > $processingConfiguration['maxWidth']
            || !$cropArea->isEmpty();
        if (!$needsProcessing) {
            return $fileReference->getOriginalFile();
        }

        return $fileReference->getOriginalFile()->process(
            ProcessedFile::CONTEXT_IMAGECROPSCALEMASK,
            $processingConfiguration,
        );
    }

    /**
     * @param array<string, mixed> $pageRecord
     */
    private function resolvePageUrl(ServerRequestInterface $request, array $pageRecord): string
    {
        if (!empty($pageRecord['canonical_link'])) {
            $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $cObj->setRequest($request);
            $cObj->start($pageRecord, 'pages');

            return $cObj->createUrl([
                'parameter' => $pageRecord['canonical_link'],
                'forceAbsoluteUrl' => true,
            ]);
        }

        $pageInformation = $request->getAttribute('frontend.page.information');
        $pageId = $pageInformation->getId();
        $pageType = $request->getAttribute('routing')->getPageType();

        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $cObj->setRequest($request);
        $cObj->start($pageRecord, 'pages');

        return $cObj->createUrl([
            'parameter' => $pageId . ',' . $pageType,
            'forceAbsoluteUrl' => true,
            'addQueryString' => true,
        ]);
    }

    private function resolveSiteName(?SiteLanguage $siteLanguage, string $siteTitle): string
    {
        if ($siteLanguage instanceof SiteLanguage) {
            $languageTitle = trim($siteLanguage->getWebsiteTitle());
            if ($languageTitle !== '') {
                return $languageTitle;
            }
        }

        return trim($siteTitle);
    }

    /**
     * @param array<string, scalar|null> $subProperties
     */
    private function addProperty(string $property, string $content, array $subProperties = []): void
    {
        $manager = $this->metaTagManagerRegistry->getManagerForProperty($property);
        $manager->addProperty($property, $content, $subProperties);
    }
}
