<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Form\Element;

use Maispace\MaiSeo\Event\StructuredDataEditorConfigEvent;
use Maispace\MaiSeo\Schema\VocabularyRegistryInterface;
use Maispace\MaiSeo\StructuredData\StructuredDataService;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Page\PageRenderer;

final class StructuredDataTreeElement extends AbstractFormElement
{
    public function __construct(
        private readonly VocabularyRegistryInterface $vocabularyRegistry,
        private readonly StructuredDataService $structuredDataService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly PageRenderer $pageRenderer,
    ) {}

    public function render(): array
    {
        $result = $this->initializeResultArray();

        $row = $this->data['databaseRow'] ?? [];
        $pageUid = (int)($row['uid'] ?? 0);
        $parameterArray = $this->data['parameterArray'];
        $fieldName = $parameterArray['itemFormElName'];
        $currentValue = $parameterArray['itemFormElValue'] ?? '';

        $autoData = $pageUid > 0
            ? $this->structuredDataService->getAutoDataForPage($pageUid)
            : [];

        $editorConfig = [
            'types' => $this->vocabularyRegistry->getTypeNames(),
            'propertiesByType' => $this->buildPropertiesByType(),
        ];

        $configEvent = new StructuredDataEditorConfigEvent($pageUid, $editorConfig);
        $this->eventDispatcher->dispatch($configEvent);
        $editorConfig = $configEvent->getConfig();

        $editorId = 'maiseo-tree-' . $pageUid . '-' . substr(md5($fieldName), 0, 8);

        $this->pageRenderer->loadJavaScriptModule('@maispace/mai-seo/tree/structured-data-tree-editor.js');
        $this->pageRenderer->addCssFile('EXT:mai_seo/Resources/Public/Css/structured-data-editor.css');

        $html = $this->buildHtml($editorId, $fieldName, $currentValue, $autoData, $editorConfig);

        $result['html'] = $html;
        return $result;
    }

    private function buildPropertiesByType(): array
    {
        $result = [];
        foreach ($this->vocabularyRegistry->getTypeNames() as $typeName) {
            $result[$typeName] = $this->vocabularyRegistry->getPropertiesForType($typeName);
        }
        return $result;
    }

    private function buildHtml(
        string $editorId,
        string $fieldName,
        string $currentValue,
        array $autoData,
        array $editorConfig,
    ): string {
        $autoDataJson = htmlspecialchars(json_encode($autoData, JSON_UNESCAPED_UNICODE), ENT_QUOTES);
        $configJson = htmlspecialchars(json_encode($editorConfig, JSON_UNESCAPED_UNICODE), ENT_QUOTES);
        $currentValue = htmlspecialchars($currentValue, ENT_QUOTES);

        return <<<HTML
<div
    id="{$editorId}"
    class="maiseo-structured-data-tree"
    data-auto="{$autoDataJson}"
    data-config="{$configJson}"
>
    <noscript><p>JavaScript is required for the structured data editor.</p></noscript>
</div>
<input type="hidden" name="{$fieldName}" value="{$currentValue}" id="{$editorId}-input" />
HTML;
    }
}
