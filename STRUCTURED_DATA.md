# Structured Data Tree Editor — Feature Plan

**Extension:** `maispace/mai-seo` (`mai_seo`)  
**TYPO3:** 13.4 LTS / 14.x  
**Status:** Planning

---

## Table of Contents

1. [Overview & Goals](#1-overview--goals)
2. [Architecture Overview](#2-architecture-overview)
3. [Data Model & Storage](#3-data-model--storage)
4. [Schema.org Vocabulary Registry](#4-schemaorg-vocabulary-registry)
5. [Backend Field — Custom TCA RenderType](#5-backend-field--custom-tca-rendertype)
6. [JavaScript Tree Editor](#6-javascript-tree-editor)
7. [Auto-Generation Pipeline](#7-auto-generation-pipeline)
8. [Content Element & Record Collectors](#8-content-element--record-collectors)
9. [Frontend Output](#9-frontend-output)
10. [Caching Strategy](#10-caching-strategy)
11. [Extension API](#11-extension-api)
12. [File & Class Map](#12-file--class-map)
13. [Implementation Phases](#13-implementation-phases)
14. [Open Questions](#14-open-questions)

---

## 1. Overview & Goals

### Problem

TYPO3 currently has no first-class, editor-friendly way to manage JSON-LD structured data per page. Editors either rely on auto-generated markup (opaque, not editable) or hand-craft raw JSON (error-prone, no guidance).

### Solution

A **visual JSON-LD tree editor** embedded in the page properties form. It:

- Shows structured data as a collapsible/expandable node tree
- Pre-populates nodes automatically from page properties and content elements
- Constrains editors to valid schema.org types and properties via inline dropdowns
- Allows full override, addition, and deletion of any node
- Serialises to a JSON string stored in a new `pages` column
- Outputs the final graph as a `<script type="application/ld+json">` tag in `<head>`

### Non-Goals

- Full JSON-LD graph validation (no SPARQL, no RDF reasoner)
- Support for external `@id` references (out of scope for v1)
- Multi-graph `@graph` arrays (single root entity per page in v1)

---

## 2. Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                      BACKEND (Page Properties)               │
│                                                               │
│  TCA Field: tx_maiseo_structured_data                        │
│  renderType: maiseoStructuredDataTree                        │
│                                                               │
│  ┌───────────────────────────────────────────────────────┐  │
│  │           JavaScript Tree Editor (ES Module)           │  │
│  │                                                        │  │
│  │  • Renders JSON-LD graph as interactive node tree      │  │
│  │  • Schema.org vocabulary dropdown (type-aware)         │  │
│  │  • Add / Remove / Edit / Reorder nodes                 │  │
│  │  • Live JSON preview panel                             │  │
│  │  • Serialises to hidden <textarea>                     │  │
│  └───────────────────────────────────────────────────────┘  │
│                          ↑ loads                              │
│  SchemaVocabularyProvider (PHP → JS inline config)           │
└─────────────────────────────────────────────────────────────┘
                           │ save
                           ▼
┌─────────────────────────────────────────────────────────────┐
│               DATABASE  pages.tx_maiseo_structured_data      │
│               (JSON string, max ~64 KB)                      │
└─────────────────────────────────────────────────────────────┘
                           │
          ┌────────────────┴────────────────┐
          │ on page load (frontend)          │ on page save (DataHandler)
          ▼                                  ▼
┌──────────────────────┐          ┌──────────────────────────┐
│  StructuredData      │          │  AutoGenerator           │
│  ViewHelper /        │          │  (PSR-14 event listener) │
│  DataProcessor       │          │  Merges auto-data into   │
│  Outputs <script>    │          │  stored JSON, caches     │
│  in <head>           │          └──────────────────────────┘
└──────────────────────┘                    │
                                            │ fires
                                            ▼
                              ┌─────────────────────────┐
                              │  Collector Registry      │
                              │  (PSR-4 tagged services) │
                              │                          │
                              │  • PageCollector         │
                              │  • BreadcrumbCollector   │
                              │  • ContentElementCollector│
                              │  • [3rd party collectors] │
                              └─────────────────────────┘
```

---

## 3. Data Model & Storage

### 3.1 New Database Column

```sql
ALTER TABLE pages ADD tx_maiseo_structured_data mediumtext DEFAULT NULL;
```

**TCA column type:** `json` (TYPO3 13+ native JSON column type, stored as text in the DB, decoded automatically by TYPO3 DataHandler internals when needed, but we treat it as a raw JSON string).

### 3.2 Stored Format

The column stores a single JSON-LD document as a UTF-8 encoded string. Example:

```json
{
  "@context": "https://schema.org",
  "@type": "WebPage",
  "name": "About Us",
  "url": "https://example.com/about",
  "breadcrumb": {
    "@type": "BreadcrumbList",
    "itemListElement": [
      {
        "@type": "ListItem",
        "position": 1,
        "name": "Home",
        "item": "https://example.com"
      },
      {
        "@type": "ListItem",
        "position": 2,
        "name": "About Us",
        "item": "https://example.com/about"
      }
    ]
  }
}
```

### 3.3 Override vs Auto-Generated Data

The stored JSON is the **editor's override layer**. The final frontend output merges:

1. **Auto-generated base** (produced by Collectors at page-save time or on first render)
2. **Editor overrides** (the stored JSON — takes priority over auto-generated values)

The merge is a **deep merge**: editor nodes win over auto nodes at every level. The editor can blank a value by explicitly setting it to an empty string or `null` — a special sentinel `"__remove__"` string removes a property entirely from the merged output.

---

## 4. Schema.org Vocabulary Registry

### 4.1 The Vocabulary Problem

Schema.org defines ~800 types and thousands of properties. The editor must present only **valid properties for the selected type**, including inherited properties. This requires a type-aware vocabulary lookup.

### 4.2 Vocabulary Source

Schema.org publishes a machine-readable vocabulary at:

- **Full JSON-LD schema:** `https://schema.org/version/latest/schemaorg-current-https.jsonld`
- **CSV list:** `https://schema.org/docs/developers.html`

For bundling, we extract a **curated subset** at build time (via a PHP CLI command or a composer script) and ship it as a static JSON file in `Resources/Public/JavaScript/`. This avoids a runtime HTTP dependency.

The vocabulary file structure:

```json
{
  "types": {
    "WebPage": {
      "label": "WebPage",
      "description": "A web page.",
      "ancestors": ["Thing", "CreativeWork"],
      "properties": ["name", "url", "description", "breadcrumb", "mainEntity", "author", "datePublished", "dateModified", "image", "inLanguage", "keywords", "isPartOf"]
    },
    "Article": {
      "label": "Article",
      "ancestors": ["Thing", "CreativeWork"],
      "properties": ["headline", "name", "author", "datePublished", "dateModified", "image", "description", "articleBody", "wordCount", "publisher"]
    }
  },
  "properties": {
    "name": { "label": "name", "expectedTypes": ["Text"], "description": "The name of the item." },
    "author": { "label": "author", "expectedTypes": ["Person", "Organization"], "description": "The author of this content." },
    "datePublished": { "label": "datePublished", "expectedTypes": ["Date", "DateTime"], "description": "Date of first broadcast/publication." }
  }
}
```

### 4.3 PHP Vocabulary Registry

```
Classes/
  Schema/
    VocabularyRegistry.php          — Singleton/DI service, loads + caches the JSON vocab
    VocabularyRegistryInterface.php — Public contract
    TypeDefinition.php              — DTO: type name, ancestors, properties list
    PropertyDefinition.php          — DTO: property name, expectedTypes, description
```

**Interface:**

```php
interface VocabularyRegistryInterface
{
    /** Returns all type names as a flat sorted list */
    public function getTypeNames(): array;

    /** Returns TypeDefinition for a given schema.org type name */
    public function getType(string $typeName): ?TypeDefinition;

    /** Returns all valid property names for a type (including inherited) */
    public function getPropertiesForType(string $typeName): array;

    /** Returns PropertyDefinition for a given property name */
    public function getProperty(string $propertyName): ?PropertyDefinition;

    /** Register an additional custom type from a third-party extension */
    public function registerType(TypeDefinition $type): void;

    /** Register additional properties on an existing type */
    public function extendType(string $typeName, array $additionalProperties): void;
}
```

The registry is populated from the bundled vocabulary file and then extended via the **PSR-14 event `VocabularyLoadedEvent`** (see §11).

### 4.4 Inline Vocabulary Config for the Editor

The PHP `renderType` backend node serialises a compact vocabulary config into the page HTML as an inline `<script>` tag or a data attribute, consumed by the JavaScript editor on init:

```json
{
  "types": ["Article", "BreadcrumbList", "ContactPage", "Event", "FAQPage", "LocalBusiness", "Organization", "Person", "Product", "WebPage", "WebSite"],
  "propertiesByType": {
    "WebPage": ["name", "url", "description", "breadcrumb", "author", "datePublished"],
    "Article": ["headline", "name", "author", "datePublished", "dateModified", "image", "description"]
  },
  "propertyMeta": {
    "author": { "expectedTypes": ["Person", "Organization"] },
    "datePublished": { "expectedTypes": ["Date"] }
  }
}
```

This keeps the JS editor thin — it does not need to load the full 800-type schema, only the types the site actually uses plus a complete property map for those types.

---

## 5. Backend Field — Custom TCA RenderType

### 5.1 TCA Column Registration

In `Configuration/TCA/Overrides/pages.php` (extends the existing file):

```php
'tx_maiseo_structured_data' => [
    'label' => $lang('pages.tx_maiseo_structured_data'),
    'description' => $lang('pages.tx_maiseo_structured_data.description'),
    'config' => [
        'type' => 'user',
        'renderType' => 'maiseoStructuredDataTree',
        'default' => '',
    ],
],
```

Added to the existing `mai_seo` palette or a new dedicated SEO tab section.

### 5.2 NodeFactory Registration

In `Configuration/Services.yaml`:

```yaml
Maispace\MaiSeo\Form\Element\StructuredDataTreeElement:
  tags:
    - name: form.formEngine.nodeType
      identifier: maiseoStructuredDataTree
      priority: 0
```

### 5.3 PHP Backend Node Class

```
Classes/
  Form/
    Element/
      StructuredDataTreeElement.php
```

```php
namespace Maispace\MaiSeo\Form\Element;

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Page\PageRenderer;

final class StructuredDataTreeElement extends AbstractNode
{
    public function __construct(
        NodeFactory $nodeFactory,
        private readonly VocabularyRegistryInterface $vocabularyRegistry,
        private readonly PageRenderer $pageRenderer,
    ) {
        parent::__construct($nodeFactory);
    }

    public function render(): array
    {
        $result = $this->initializeResultArray();

        // Load the ES Module for the tree editor
        $this->pageRenderer->loadJavaScriptModule(
            '@maispace/mai-seo/structured-data-tree-editor.js'
        );

        // Inline the compact vocabulary config
        $vocabConfig = $this->buildVocabConfig();

        // Current stored value
        $currentValue = $this->data['parameterArray']['itemFormElValue'] ?? '{}';

        // Render HTML container — the JS editor mounts here
        $html = $this->renderEditorHtml(
            fieldId: $this->data['parameterArray']['itemFormElID'],
            fieldName: $this->data['parameterArray']['itemFormElName'],
            currentValue: $currentValue,
            vocabConfig: $vocabConfig,
        );

        $result['html'] = $html;
        return $result;
    }

    private function buildVocabConfig(): array { /* ... */ }
    private function renderEditorHtml(...): string { /* ... */ }
}
```

The `render()` method returns the standard TYPO3 FormEngine result array. The `html` key contains the field markup including:

- A hidden `<textarea>` (the actual form field TYPO3 will save)
- A `<div data-module="@maispace/mai-seo/structured-data-tree-editor">` mount point
- An inline `<script type="application/json" id="maiseo-vocab-config-{uid}">` with the vocabulary config
- An inline `<script type="application/json" id="maiseo-current-value-{uid}">` with the current JSON

### 5.4 ES Module Registration

In `Configuration/JavaScriptModules.php`:

```php
return [
    'dependencies' => ['backend', 'core'],
    'tags' => ['backend.form'],
    'imports' => [
        '@maispace/mai-seo/' => 'EXT:mai_seo/Resources/Public/JavaScript/',
    ],
];
```

---

## 6. JavaScript Tree Editor

### 6.1 Technology Choices

| Concern | Choice | Rationale |
|---|---|---|
| Module format | Native ES Module | TYPO3 13+ standard; no RequireJS |
| Rendering | Vanilla JS + DOM API | Zero heavy dependencies; fits FormEngine |
| State management | Simple reactive state object | No framework overhead needed |
| Styling | TYPO3 Bootstrap 5 classes | Consistent with backend UI |
| Build | No bundler needed | ES modules load natively in TYPO3 13 |

### 6.2 File Structure

```
Resources/Public/JavaScript/
  structured-data-tree-editor.js   — Main editor module (entry point)
  tree/
    TreeNode.js                    — Node component renderer
    NodeEditor.js                  — Inline property editor
    PropertyDropdown.js            — Schema.org property selector
    TypeDropdown.js                — Schema.org type selector
  schema/
    VocabularyConfig.js            — Parses inline vocab config
    PropertyConstraints.js         — Determines valid props for a type
  util/
    JsonMerge.js                   — Deep merge utility
    JsonSerializer.js              — Tree state → JSON-LD string
```

### 6.3 Editor UI Anatomy

```
┌─ Structured Data ──────────────────────────────────────────────────┐
│  Root type: [WebPage ▼]                    [+ Add Property]  [{}]  │
│                                                                      │
│  ▼ name           "About Us"                              [✎] [✕]  │
│  ▼ url            "https://example.com/about"             [✎] [✕]  │
│  ► breadcrumb     {BreadcrumbList}         [+ Add] [✎] [✕]        │
│    ► itemListElement  [array]              [+ Add] [✎] [✕]        │
│      ► [0]  {ListItem}                    [+ Add] [✎] [✕]        │
│          position    1                              [✎] [✕]        │
│          name        "Home"                         [✎] [✕]        │
│          item        "https://example.com"          [✎] [✕]        │
│                                                                      │
│  ░░░ Auto-generated (from page + content) ░░░░░░░░░░░░░░░░░░░░░░  │
│  ▼ author         {Person} · from: page meta                       │
│  ▼ datePublished  "2024-01-15"  · from: page creation date         │
│                                                                      │
│  [Reset to Auto]  [Preview JSON-LD]                                 │
└────────────────────────────────────────────────────────────────────┘
```

**Key UI rules:**

1. **Root @type dropdown** — always visible at the top; changing it resets all type-specific properties (with a confirmation dialog)
2. **Property name dropdown** — when adding a new property, shows only valid properties for the current node's type (from `PropertyConstraints.js`)
3. **Inline value editor** — text input for `Text`/`URL`/`Date`/`Number` values; "Add Nested Object" button for `expectedTypes` that are schema.org types (renders a child node with its own type dropdown)
4. **Auto-generated nodes** — shown with a visual distinction (lighter background, lock icon); can be overridden (click edit → unlocks the node, stores it in the override layer)
5. **Array properties** — rendered as an ordered list with drag-handle reordering; "Add item" button appends a new node
6. **JSON preview** — a toggleable raw JSON panel showing the final merged output (read-only)

### 6.4 State Model (JavaScript)

```js
// Internal state — never stored directly
const editorState = {
    rootType: 'WebPage',                  // @type of root entity
    nodes: [                               // Flat list of all nodes
        {
            id: 'node_1',
            path: ['name'],               // Property path from root
            value: 'About Us',            // Scalar or null (if nested)
            type: null,                   // schema.org type if nested entity
            isAuto: false,                // True if from AutoGenerator
            isOverridden: false,          // True if editor explicitly overrode an auto node
            children: [],                 // Child node ids
        }
    ],
    isDirty: false,
};
```

On every state mutation, `JsonSerializer.js` serialises the override nodes (non-auto + overridden-auto) to a JSON string and writes it into the hidden `<textarea>`.

### 6.5 Initialisation Flow

```js
// structured-data-tree-editor.js
export default class StructuredDataTreeEditor {
    constructor(mountEl, textareaEl, vocabConfig, initialValue, autoValue) {
        this.state = buildInitialState(initialValue, autoValue, vocabConfig);
        this.mountEl = mountEl;
        this.textareaEl = textareaEl;
        this.render();
    }
}

// TYPO3 FormEngine calls this via the module loader
document.querySelectorAll('[data-module="@maispace/mai-seo/structured-data-tree-editor"]')
    .forEach(el => {
        const config = JSON.parse(document.getElementById(el.dataset.vocabId).textContent);
        const current = JSON.parse(document.getElementById(el.dataset.currentId).textContent);
        const auto = JSON.parse(el.dataset.autoValue || '{}');
        const textarea = document.getElementById(el.dataset.fieldId);
        new StructuredDataTreeEditor(el, textarea, config, current, auto);
    });
```

---

## 7. Auto-Generation Pipeline

### 7.1 Trigger Points

Auto-generation runs at two points:

| Trigger | Mechanism | Purpose |
|---|---|---|
| **Page saved** (backend) | PSR-14 `AfterRecordSummaryForLocalizationEvent` + DataHandler `processDatamap_afterDatabaseOperations` hook | Regenerate auto-data and store in a separate cache entry |
| **First page render** (frontend) | `StructuredDataDataProcessor` falls back to on-demand generation if cache miss | Handles cold starts / imports |

### 7.2 DataHandler Hook / PSR-14 Event

TYPO3 13 approach — using the `AfterRecordPublishedEvent` (or `DataHandlerProcessDatamapAfterDatabaseOperationsEvent` in future TYPO3 versions) and the existing `processDatamap_afterDatabaseOperations` hook as a bridge:

```php
// Configuration/Services.yaml
Maispace\MaiSeo\EventListener\StructuredDataAutoGeneratorListener:
  tags:
    - name: event.listener
      identifier: maiseo.structureddata.autogenerate
      event: TYPO3\CMS\Core\DataHandling\Event\IsTableExcludedFromReferenceIndexEvent
```

> **Note:** The most reliable hook for "a page record was saved" in TYPO3 13 is still the classic `$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']` hook interface `DataHandlerProcessUploadHookInterface` — specifically the `processDatamap_afterDatabaseOperations` method, triggered for `pages` table changes. This should be wrapped in a slim adapter class that dispatches a custom PSR-14 event `PageRecordSavedEvent` for cleaner extension.

### 7.3 Custom PSR-14 Events for Generation

```php
// Classes/Event/PageRecordSavedEvent.php
final class PageRecordSavedEvent
{
    public function __construct(
        public readonly int $pageUid,
        public readonly array $fieldArray,   // Changed fields
        public readonly string $status,      // 'new' | 'update'
    ) {}
}

// Classes/Event/StructuredDataCollectionEvent.php
final class StructuredDataCollectionEvent
{
    public function __construct(
        public readonly int $pageUid,
        public readonly array $pageRecord,
        private array $graph = [],           // Accumulated JSON-LD data
    ) {}

    public function addToGraph(string $property, mixed $value): void
    {
        $this->graph[$property] = $value;
    }

    public function mergeIntoGraph(array $data): void
    {
        $this->graph = array_merge_recursive($this->graph, $data);
    }

    public function getGraph(): array { return $this->graph; }
    public function setRootType(string $type): void { $this->graph['@type'] = $type; }
}
```

### 7.4 AutoGenerator Service

```
Classes/
  StructuredData/
    AutoGenerator.php
```

```php
final class AutoGenerator
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly CollectorRegistry $collectorRegistry,
        private readonly CacheManager $cacheManager,
    ) {}

    public function generateForPage(int $pageUid, array $pageRecord): array
    {
        $event = new StructuredDataCollectionEvent($pageUid, $pageRecord);

        // Fire all registered collectors in priority order
        foreach ($this->collectorRegistry->getCollectors() as $collector) {
            $collector->collect($event);
        }

        // Let external listeners also contribute
        $this->eventDispatcher->dispatch($event);

        $graph = array_merge(['@context' => 'https://schema.org'], $event->getGraph());

        // Cache the result
        $cache = $this->cacheManager->getCache('maiseo_structured_data');
        $cache->set('auto_' . $pageUid, $graph, ['pageId_' . $pageUid]);

        return $graph;
    }
}
```

---

## 8. Content Element & Record Collectors

Collectors are tagged DI services implementing `CollectorInterface`. They receive the `StructuredDataCollectionEvent` and contribute auto-generated data.

### 8.1 CollectorInterface

```php
// Classes/StructuredData/Collector/CollectorInterface.php
interface CollectorInterface
{
    /**
     * Inspect the page and its content, then add data to the event graph.
     */
    public function collect(StructuredDataCollectionEvent $event): void;

    /**
     * Returns the schema.org type(s) this collector handles.
     * Used to determine whether to run the collector for a given page schema type.
     */
    public function supportedTypes(): array;

    /**
     * Execution priority. Higher runs first.
     */
    public function priority(): int;
}
```

### 8.2 Collector DI Tag

```yaml
# Services.yaml
_instanceof:
  Maispace\MaiSeo\StructuredData\Collector\CollectorInterface:
    tags:
      - name: maiseo.structured_data.collector
```

### 8.3 Built-in Collectors

#### `PageCollector`

Reads page record fields:

- `title` → `name`
- `description` → `description`
- `canonical_link` → `url`
- `tx_maiseo_og_image` → `image`
- `crdate` → `datePublished`
- `tstamp` → `dateModified`
- `author` (if field exists) → `author`

```php
final class PageCollector implements CollectorInterface
{
    public function collect(StructuredDataCollectionEvent $event): void
    {
        $record = $event->getPageRecord();
        $event->addToGraph('name', $record['title'] ?? '');
        $event->addToGraph('description', $record['description'] ?? '');
        // ...
    }

    public function supportedTypes(): array { return ['*']; } // All types
    public function priority(): int { return 100; }
}
```

#### `BreadcrumbCollector`

Walks the rootline to build a `BreadcrumbList` and appends it to the graph:

```php
final class BreadcrumbCollector implements CollectorInterface
{
    public function collect(StructuredDataCollectionEvent $event): void
    {
        $rootline = $this->rootlineUtility->getRootline($event->getPageUid());
        $items = [];
        foreach (array_reverse($rootline) as $pos => $page) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $pos + 1,
                'name' => $page['title'],
                'item' => $this->generatePageUrl($page['uid']),
            ];
        }
        $event->addToGraph('breadcrumb', [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ]);
    }

    public function supportedTypes(): array { return ['*']; }
    public function priority(): int { return 90; }
}
```

#### `ContentElementCollector`

Scans content elements on the page and extracts relevant structured data:

- **`text` / `textpic`** elements with FAQ marker → contributes `FAQPage.mainEntity` items
- **`tx_news` records** linked on page → contributes `Article` or `NewsArticle` nodes
- **`tx_events2`** records → contributes `Event` nodes
- Controlled by a mapping configuration (see §11.3)

```php
final class ContentElementCollector implements CollectorInterface
{
    public function collect(StructuredDataCollectionEvent $event): void
    {
        $elements = $this->fetchContentElements($event->getPageUid());
        foreach ($elements as $element) {
            $handler = $this->handlerRegistry->getHandlerForCType($element['CType']);
            $handler?->handleElement($element, $event);
        }
    }

    public function supportedTypes(): array { return ['*']; }
    public function priority(): int { return 50; }
}
```

### 8.4 Content Element Handler Interface

Third-party extensions (e.g., `tx_news`, custom plugins) can register handlers:

```php
// Classes/StructuredData/ContentElement/ContentElementHandlerInterface.php
interface ContentElementHandlerInterface
{
    /** The CType(s) or list_type(s) this handler processes */
    public function getSupportedCTypes(): array;

    /** Extract structured data from the element and add to the event graph */
    public function handleElement(array $ttContentRecord, StructuredDataCollectionEvent $event): void;
}
```

Registration via DI tag:

```yaml
_instanceof:
  Maispace\MaiSeo\StructuredData\ContentElement\ContentElementHandlerInterface:
    tags:
      - name: maiseo.structured_data.content_element_handler
```

---

## 9. Frontend Output

### 9.1 Data Processor

```
Classes/
  DataProcessing/
    StructuredDataProcessor.php
```

```php
final class StructuredDataProcessor implements DataProcessorInterface
{
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData,
    ): array {
        $pageUid = $GLOBALS['TSFE']->id;
        $pageRecord = $processedData['page'] ?? [];

        // 1. Load auto-generated graph from cache (or generate on miss)
        $autoGraph = $this->autoGenerator->getOrGenerateForPage($pageUid, $pageRecord);

        // 2. Load editor overrides from the page record JSON field
        $overrideJson = $pageRecord['tx_maiseo_structured_data'] ?? '{}';
        $overrides = json_decode($overrideJson, true) ?: [];

        // 3. Deep-merge: auto as base, overrides win
        $finalGraph = $this->deepMerge($autoGraph, $overrides);

        // 4. Apply __remove__ sentinel removal
        $finalGraph = $this->applySentinelRemovals($finalGraph);

        $processedData['structuredData'] = $finalGraph;
        $processedData['structuredDataJson'] = json_encode($finalGraph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return $processedData;
    }
}
```

### 9.2 TypoScript / Fluid Integration

```typo3_typoscript
page.10 = FLUIDTEMPLATE
page.10 {
    dataProcessing {
        10 = Maispace\MaiSeo\DataProcessing\StructuredDataProcessor
        10 {
            as = structuredData
        }
    }
}
```

In the Fluid template:

```html
<f:if condition="{structuredDataJson}">
    <script type="application/ld+json">{structuredDataJson -> f:format.raw()}</script>
</f:if>
```

Alternatively, the output can be injected directly into `<head>` via `PageRenderer->addHeaderData()` in the DataProcessor itself, bypassing the template layer — a TypoScript constant can toggle this behaviour.

### 9.3 ViewHelper (alternative)

```
Classes/
  ViewHelpers/
    StructuredDataViewHelper.php
```

```html
<maiseo:structuredData page="{page}" />
```

Outputs the `<script type="application/ld+json">` tag directly. Useful for non-DataProcessor setups.

---

## 10. Caching Strategy

### 10.1 Cache Configuration

```php
// ext_localconf.php
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['maiseo_structured_data'] ??= [
    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
    'backend'  => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
    'options'  => ['defaultLifetime' => 86400],
    'groups'   => ['pages'],
];
```

### 10.2 Cache Keys

| Key Pattern | Contents | Invalidated by |
|---|---|---|
| `auto_{pageUid}` | Auto-generated graph array | Page saved, content element saved, clear all caches |
| `final_{pageUid}_{lang}` | Merged final graph JSON | Page saved, override JSON changed, clear all caches |

Cache tags: `pageId_{uid}` — allows precise tag-based invalidation when a page or its content is modified.

### 10.3 Invalidation Trigger

The `PageRecordSavedEvent` listener clears `auto_{pageUid}` and `final_{pageUid}_*` entries. Content element changes trigger invalidation via the existing TYPO3 `tt_content` DataHandler hook, checking the `pid` of the modified record.

---

## 11. Extension API

This section defines all touch points for third-party extensions to extend the structured data feature.

### 11.1 PSR-14 Events

#### `VocabularyLoadedEvent`

Fired after the core vocabulary registry is loaded. Listeners can add custom types or extend existing ones.

```php
final class VocabularyLoadedEvent
{
    public function __construct(
        private readonly VocabularyRegistryInterface $registry,
    ) {}

    public function getRegistry(): VocabularyRegistryInterface
    {
        return $this->registry;
    }
}
```

**Example usage (third-party extension):**

```php
final class AddCustomSchemaTypesListener
{
    public function __invoke(VocabularyLoadedEvent $event): void
    {
        $event->getRegistry()->registerType(new TypeDefinition(
            name: 'MedicalClinic',
            label: 'Medical Clinic',
            ancestors: ['LocalBusiness', 'Organization', 'Thing'],
            properties: ['medicalSpecialty', 'availableService', 'name', 'address', 'telephone'],
        ));

        $event->getRegistry()->extendType('LocalBusiness', ['hasMap', 'currenciesAccepted']);
    }
}
```

#### `StructuredDataCollectionEvent`

Already defined in §7.3. External extensions listen to add data without writing a full Collector:

```php
final class AddNewsArticleDataListener
{
    public function __invoke(StructuredDataCollectionEvent $event): void
    {
        // Only run for pages that have a news article in their content
        $newsRecord = $this->fetchNewsForPage($event->getPageUid());
        if ($newsRecord === null) return;

        $event->mergeIntoGraph([
            '@type' => 'NewsArticle',
            'headline' => $newsRecord['title'],
            'datePublished' => date('c', $newsRecord['datetime']),
        ]);
    }
}
```

#### `StructuredDataRenderEvent`

Fired just before the final JSON is serialised for output. Allows last-minute modifications.

```php
final class StructuredDataRenderEvent
{
    public function __construct(
        public readonly int $pageUid,
        private array $graph,
    ) {}

    public function getGraph(): array { return $this->graph; }

    public function setGraph(array $graph): void { $this->graph = $graph; }

    public function removeProperty(string $property): void
    {
        unset($this->graph[$property]);
    }
}
```

#### `StructuredDataEditorConfigEvent`

Fired when the backend field builds its inline vocabulary config for the JS editor. Listeners can add types/properties to the editor's dropdown without modifying the core registry.

```php
final class StructuredDataEditorConfigEvent
{
    public function __construct(
        public readonly int $pageUid,
        private array $editorConfig,  // The inline config array for the JS editor
    ) {}

    public function getConfig(): array { return $this->editorConfig; }
    public function setConfig(array $config): void { $this->editorConfig = $config; }
    public function addType(string $typeName, array $properties): void { /* ... */ }
}
```

### 11.2 DI-Tagged Services

| Tag | Interface | Purpose |
|---|---|---|
| `maiseo.structured_data.collector` | `CollectorInterface` | Contribute auto-generated data at page-save time |
| `maiseo.structured_data.content_element_handler` | `ContentElementHandlerInterface` | Handle specific CTypes/plugins in ContentElementCollector |

### 11.3 Content Element Handler Mapping (via DI + Tag Attributes)

Third-party extensions register handlers by tagging their service and declaring which CTypes they handle via the `getSupportedCTypes()` method. No XML/YAML mapping file required — the `ContentElementHandlerRegistry` auto-discovers all tagged services.

### 11.4 TypoScript Configuration API

All behaviour can be tuned via TypoScript constants:

```typo3_typoscript
plugin.tx_maiseo {
    settings {
        structuredData {
            # Enable/disable the auto-generation pipeline
            autoGenerate = 1

            # Inject <script> directly via PageRenderer (bypasses Fluid template)
            injectViaPageRenderer = 0

            # Default root @type for new pages (overridable per page in the editor)
            defaultRootType = WebPage

            # Comma-separated list of schema.org types shown in the editor type dropdown
            # Leave empty to show all registered types
            editorAllowedTypes =

            # Show auto-generated nodes in the editor (with lock indicator)
            showAutoNodes = 1

            # Maximum JSON-LD nesting depth allowed in the editor
            maxNestingDepth = 5
        }
    }
}
```

### 11.5 PHP API Surface Summary

```php
// Retrieve the final merged structured data for a page (for use in custom PHP code)
$structuredDataService = GeneralUtility::makeInstance(StructuredDataService::class);
$graph  = $structuredDataService->getFinalGraph(pageUid: 42, languageUid: 0);
$json   = $structuredDataService->getFinalJson(pageUid: 42, languageUid: 0);

// Force-regenerate auto data for a page (e.g., after an import)
$autoGenerator = GeneralUtility::makeInstance(AutoGenerator::class);
$autoGenerator->generateForPage(pageUid: 42, pageRecord: $record);

// Access the vocabulary registry
$registry = GeneralUtility::makeInstance(VocabularyRegistryInterface::class);
$props    = $registry->getPropertiesForType('Article');
```

---

## 12. File & Class Map

```
Classes/
  Form/
    Element/
      StructuredDataTreeElement.php        — TCA custom renderType backend node

  Schema/
    VocabularyRegistry.php                 — Loads + manages schema.org vocab
    VocabularyRegistryInterface.php
    TypeDefinition.php                     — DTO for a schema.org type
    PropertyDefinition.php                 — DTO for a schema.org property

  StructuredData/
    AutoGenerator.php                      — Orchestrates all collectors, caches result
    StructuredDataService.php             — Public service: get final graph/JSON for a page
    CollectorRegistry.php                  — Discovers + sorts all tagged collectors

    Collector/
      CollectorInterface.php
      PageCollector.php
      BreadcrumbCollector.php
      ContentElementCollector.php
      OrganizationCollector.php            — Reads site config and settings.yaml for Organization data

    ContentElement/
      ContentElementHandlerInterface.php
      ContentElementHandlerRegistry.php
      Handler/
        TextHandler.php                    — Detects FAQ content in text elements
        PluginHandler.php                  — Base class for plugin-based handlers

  DataProcessing/
    StructuredDataProcessor.php            — TYPO3 DataProcessor for Fluid templates

  ViewHelpers/
    StructuredDataViewHelper.php           — <maiseo:structuredData> Fluid ViewHelper

  EventListener/
    PageRecordSavedListener.php            — Triggers auto-regeneration on page save
    ContentElementSavedListener.php        — Triggers invalidation on tt_content save

  Event/
    VocabularyLoadedEvent.php
    StructuredDataCollectionEvent.php
    StructuredDataRenderEvent.php
    StructuredDataEditorConfigEvent.php
    PageRecordSavedEvent.php

  Utility/
    JsonMerge.php                          — Deep merge with __remove__ sentinel support

Configuration/
  TCA/Overrides/
    pages.php                              — (extended) adds tx_maiseo_structured_data column

  JavaScriptModules.php                    — Registers ES module import map entry

  Services.yaml                            — (extended) registers renderType + all DI tags

Resources/
  Private/
    Language/
      Default/
        locallang_tca.xlf                  — (extended) labels for new field

  Public/
    JavaScript/
      structured-data-tree-editor.js       — ES Module entry point
      tree/
        TreeNode.js
        NodeEditor.js
        PropertyDropdown.js
        TypeDropdown.js
      schema/
        VocabularyConfig.js
        PropertyConstraints.js
        vocabulary.json                    — Bundled curated schema.org vocab subset
      util/
        JsonMerge.js
        JsonSerializer.js

    Css/
      structured-data-editor.css           — Minimal styling for the tree editor

ext_localconf.php                          — Cache configuration, DataHandler hooks
ext_tables.sql                             — Column definition for tx_maiseo_structured_data
```

---

## 13. Implementation Phases

### Phase 1 — Core Infrastructure (v1.0)

**Goal:** Store and output editor-defined structured data; no auto-generation yet.

- [ ] Add `tx_maiseo_structured_data` DB column + TCA column
- [ ] Implement `StructuredDataTreeElement` PHP backend node (renders a basic textarea with JSON syntax highlighting as a v1 fallback)
- [ ] Implement `VocabularyRegistry` with bundled vocab JSON (top 20 types)
- [ ] Basic JS editor: root type selector + flat property list (no nesting in v1)
- [ ] `StructuredDataProcessor` — reads stored JSON, outputs `<script>` tag
- [ ] `StructuredDataViewHelper` — alternative output path

**Deliverable:** Editors can manually build flat JSON-LD; it appears in `<head>`.

---

### Phase 2 — Auto-Generation (v1.1)

**Goal:** Pre-populate structured data from page properties.

- [ ] `PageRecordSavedEvent` + DataHandler hook bridge
- [ ] `AutoGenerator` + `CollectorRegistry`
- [ ] `PageCollector` + `BreadcrumbCollector`
- [ ] Cache layer (`maiseo_structured_data` cache)
- [ ] Deep merge logic (`JsonMerge`) with `__remove__` sentinel
- [ ] Show auto-generated nodes in editor (locked, overridable)

**Deliverable:** Page save triggers structured data auto-population; editors see pre-filled values.

---

### Phase 3 — Full Tree Editor (v1.2)

**Goal:** Rich visual tree editing with nested entities.

- [ ] Nested node rendering (full tree, not flat list)
- [ ] Drag-and-drop reordering of array items
- [ ] Type-aware property dropdowns (inherited properties shown)
- [ ] Nested entity creation (add a child node with its own `@type`)
- [ ] JSON preview panel
- [ ] "Reset to Auto" action per node and globally

**Deliverable:** Full tree editor UX as described in §6.3.

---

### Phase 4 — Content Element Integration (v1.3)

**Goal:** Content on the page automatically enriches structured data.

- [ ] `ContentElementCollector` + `ContentElementHandlerRegistry`
- [ ] `TextHandler` (FAQ detection via CSS class or structured content pattern)
- [ ] `ContentElementSavedListener` (invalidation on `tt_content` saves)
- [ ] Documentation: how to write a `ContentElementHandlerInterface` implementation

**Deliverable:** FAQ content elements automatically generate `FAQPage` markup; others can extend.

---

### Phase 5 — Extension API Hardening (v2.0)

**Goal:** Public, stable API for third-party extensions.

- [ ] All PSR-14 events finalised and documented
- [ ] DI tag documentation
- [ ] TypoScript configuration documented
- [ ] Example extension skeleton (`mai_seo_news_connector`)
- [x] `OrganizationCollector` reading from site configuration
- [ ] Multi-language support (per-language override JSON)

---

## 14. Open Questions

| # | Question | Impact | Suggested Resolution |
|---|---|---|---|
| 1 | Should the stored JSON include auto-generated nodes, or only editor overrides? | Deep merge complexity; DB size | **Store only overrides** — cleaner, smaller, always re-merges on render |
| 2 | Multi-graph (`@graph` array) vs single root entity? | Schema complexity; editor UX | **Single root entity in v1**; `@graph` as a v2 enhancement |
| 3 | Should the vocabulary JSON be bundled in the extension, or fetched from schema.org at install time (e.g., composer post-install script)? | Bundle size vs freshness | **Bundle a curated subset**; provide a CLI command `vendor/bin/typo3 maiseo:update-vocabulary` to refresh |
| 4 | How to handle schema.org property values that are `URL` type — should the editor show a page-picker (link wizard)? | Editor UX | Yes — detect `expectedTypes: ['URL']` and offer TYPO3 link wizard as optional input mode |
| 5 | `__remove__` sentinel: use a JSON `null` instead of a magic string? | API clarity | `null` is cleaner; but needs distinction from "not set" vs "explicitly removed" — use a dedicated `{"__op": "remove"}` object instead |
| 6 | Should the `StructuredDataRenderEvent` allow async data fetching (e.g., live API lookups)? | Performance risk | No — all listeners must be synchronous; use pre-cached data in collectors |
| 7 | TYPO3 workspace support? | Staging workflows | Store the override JSON as a workspace-overlayable field (default TYPO3 versioning handles this if the column is added to the workspace overlay mechanism) |
| 8 | How many schema.org types to include in the bundled vocab? | JS payload size; editor usability | Start with ~50 most common web types; expose a filter via TypoScript to limit the dropdown further per project |
