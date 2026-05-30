## Structured Data & Meta Tags

* JSON-LD structured data — auto-generated schema.org markup per page via a PSR-14 collector pipeline
* Open Graph tags — `og:title`, `og:description`, `og:image` override fields added to page properties
* Schema type selector — per-page `@type` override (13 choices: Organization, WebPage, AboutPage, ContactPage, Article, BreadcrumbList, Event, FAQPage, LocalBusiness, Person, Product, WebSite, or auto)
* Visual JSON-LD editor — backend custom TCA `renderType` tree editor for manual structured data overrides
* Editor/auto merge — editor overrides are deep-merged over auto-generated data; `__remove__` sentinel deletes a property

## Structured Data Pipeline

Auto-generation runs at two points:

| Trigger | Mechanism | Purpose |
|---|---|---|
| Page saved (backend) | `PageRecordSaveHook::processDatamap_afterDatabaseOperations` fires `PageRecordSavedEvent` | Invalidates the cached graph so it is regenerated on next render |
| Frontend page render | `StructuredDataProcessor` (DataProcessor) or `<maiseo:structuredData>` ViewHelper | Calls `AutoGenerator::generateForPage()` on cache miss |

**Pipeline steps inside `AutoGenerator::generateForPage()`:**

1. Check `maiseo_structured_data` cache (`auto_{pageUid}` key). Return cached result if present.
2. Fetch the page record from the `pages` table.
3. Dispatch `StructuredDataCollectionEvent` through all registered `CollectorInterface` services in priority-descending order.
4. Add `@context: https://schema.org` to the graph.
5. Dispatch `StructuredDataRenderEvent` — last-chance for external modification.
6. Cache the graph under `auto_{pageUid}` with a 24-hour TTL (tagged `pageId_{uid}`).

**Merge layer in `StructuredDataService::getForPage()`:**

1. Load auto-generated graph via `AutoGenerator::generateForPage()`.
2. Load editor overrides from `pages.tx_maiseo_structured_data` (JSON string, empty string = no override).
3. `JsonMerge::deepMerge($autoData, $editorData)` — editor keys win at every level.
4. `__remove__` sentinel values are stripped from the merged result.

**Frontend output** (from `StructuredDataProcessor`, variable name `structuredData`):

```typo3_typoscript
page.10.dataProcessing {
    200 = Maispace\MaiSeo\DataProcessing\StructuredDataProcessor
    200.as = structuredData
}
```

The `StructuredDataViewHelper` is the alternative output path — call `<maiseo:structuredData />` in a Fluid template to emit the `<script type="application/ld+json">` tag directly.

## Auto-generated Properties

Two collectors run on every page, priority-ordered (higher runs first):

### PageCollector (priority 100, supportedTypes: `*`)

Reads the `pages` record:

| Page field | JSON-LD property | Condition |
|---|---|---|
| `title` | `name` | not empty |
| `description` | `description` | not empty |
| `canonical_link` | `url` | not empty |
| `crdate` | `datePublished` | not empty (ISO 8601 datetime) |
| `tstamp` | `dateModified` | not empty (ISO 8601 datetime) |
| `tx_maiseo_schema_type` | `@type` | not empty; falls back to `WebPage` if `@type` is still unset |

The page `@type` defaults to `WebPage` unless the editor has selected a specific type via the `tx_maiseo_schema_type` TCA field.

### BreadcrumbCollector (priority 90, supportedTypes: `*`)

Builds a `BreadcrumbList` from the page rootline:

1. Fetches rootline via `RootlineUtility` (wraps in try/catch — returns silently on error).
2. Filters out sys-folder pages (`doktype=254`) and pages with empty titles.
3. Aborts if fewer than 2 pages remain after filtering (no breadcrumb for root or first-level pages).
4. Builds `ListItem` array in top-to-bottom order (`position` 1 = root).
5. Adds `breadcrumb: {BreadcrumbList, itemListElement: [...]}` to the graph.

Breadcrumb item URLs are generated via the site router (`PageRouter::generateUri()`),
producing clean, absolute slug-based URLs (e.g. `https://example.com/about` instead
of `?id=N`).

## Schema.org Vocabulary Registry

The `VocabularyRegistry` singleton loads its type and property definitions from
`Resources/Public/JavaScript/schema/vocabulary.json` on first access. If the file is absent it falls back to a small hardcoded vocabulary.

After loading, the `VocabularyLoadedEvent` is dispatched so downstream extensions can register additional types or extend existing property lists.

**Built-in types (15):**

| Type | Ancestors | Key properties |
|---|---|---|
| `Thing` | — | name, description, url, image, identifier, sameAs |
| `WebPage` | Thing | name, url, description, breadcrumb, mainEntity, author, datePublished, dateModified, image, inLanguage, keywords, isPartOf |
| `WebSite` | Thing | name, url, description, inLanguage, potentialAction, publisher |
| `AboutPage` | Thing, WebPage | name, url, description, author, datePublished, dateModified |
| `ContactPage` | Thing, WebPage | name, url, description, contactType, contactOption |
| `FAQPage` | Thing, WebPage | name, url, description, mainEntity |
| `Article` | Thing | headline, name, author, datePublished, dateModified, image, description, articleBody, wordCount, publisher, keywords |
| `Organization` | Thing | name, url, logo, address, telephone, email, sameAs, description, numberOfEmployees, foundingDate, legalName |
| `LocalBusiness` | Thing, Organization | name, url, address, telephone, openingHours, geo, priceRange, image, currenciesAccepted, paymentAccepted |
| `Person` | Thing | name, url, image, email, jobTitle, worksFor, sameAs, birthDate, birthPlace, alumniOf |
| `Event` | Thing | name, startDate, endDate, location, description, image, url, organizer, performer, eventStatus, eventAttendanceMode |
| `Product` | Thing | name, description, image, sku, brand, offers, aggregateRating, review, gtin, mpn |
| `BreadcrumbList` | Thing | itemListElement, name, description |
| `ImageObject` | Thing | url, width, height, caption, description, contentUrl |
| `PostalAddress` | Thing | streetAddress, addressLocality, addressRegion, postalCode, addressCountry |

`VocabularyRegistry::getPropertiesForType(string $typeName)` returns the type's own properties merged with all ancestor properties (de-duplicated).

## Schema Type Coverage Audit

This section maps every functional page type in the `bgm-pulheim.org` site to its recommended schema.org `@type`, documents current auto-collector support, and identifies gaps.

**Coverage key:**
- ✅ Auto — at least one collector contributes the critical properties automatically
- 🔧 Manual — editor must set `tx_maiseo_schema_type` per page; no collector fills the domain fields
- ❌ Gap — recommended type absent from vocabulary or critical properties cannot be auto-filled today

| Functional page type | Recommended `@type` | Auto support | Gap / notes |
|---|---|---|---|
| Homepage | `Organization` | ✅ Auto | `OrganizationCollector` reads site config (name, url) and settings.yaml (logo) for the homepage; `address`, `telephone`, `foundingDate` still require manual editor overrides |
| News listing | `WebPage` | ✅ Auto (default) | No gap — generic `WebPage` with breadcrumb is appropriate |
| News detail | `Article` | 🔧 Manual | Set `tx_maiseo_schema_type=Article` per page; PageCollector maps `title→name`, `crdate→datePublished`, `tstamp→dateModified`; `headline`, `articleBody`, `author` must be added manually or by a future news connector |
| FAQ page | `FAQPage` | 🔧 Manual | Set `tx_maiseo_schema_type=FAQPage`; `mainEntity` (the Q&A pairs from `mai_faq`) is not auto-populated — a `FAQCollector` listening to `StructuredDataCollectionEvent` would pull `tx_maifaq_faq` records and build the `mainEntity` array |
| Events listing | `WebPage` | ✅ Auto (default) | No gap |
| Event detail | `Event` | 🔧 Manual | Set `tx_maiseo_schema_type=Event`; `startDate`, `endDate`, `location`, `organizer` must be added via editor or a future `EventCollector` reading from `tx_maievents_event`; note: `RegistrationController::sendConfirmation` still uses the deprecated `MailMessage::send()` (tracked in events-2 baseline) |
| Gallery listing | `WebPage` or `CollectionPage` | ✅ Auto (default as `WebPage`) | ❌ `CollectionPage` and `ItemList` types absent from vocabulary.json and TCA select; add via `VocabularyLoadedEvent` listener if needed |
| Job listing | `WebPage` | ✅ Auto (default) | No gap |
| Job detail | `JobPosting` | ❌ Gap | `JobPosting` absent from vocabulary.json and TCA select; documented as planned in `mai_jobs` FEATURES.md §10; register via `VocabularyLoadedEvent` and fill properties from `tx_maijobs_job` record |
| Location detail | `LocalBusiness` | 🔧 Manual | Set `tx_maiseo_schema_type=LocalBusiness`; `address`, `telephone`, `openingHours`, `geo` must be added via editor or a future `LocationCollector` reading from `tx_mailocations_location`; `openingHours` must be an array of `OpeningHoursSpecification` objects |
| Member directory | `WebPage` | ✅ Auto (default) | `ProfilePage` not in vocabulary; individual member profiles are not individually indexed so `WebPage` is sufficient |
| Team directory | `WebPage` | ✅ Auto (default) | `ProfilePage` absent from vocabulary; no gap for the listing page |
| Canteen / menu | `WebPage` | ✅ Auto (default) | `FoodEstablishment` and `Menu` absent from vocabulary; no structured schema added value for a weekly canteen plan |
| Testimonials | `WebPage` | ✅ Auto (default) | `Review` and `AggregateRating` absent from vocabulary; add via `VocabularyLoadedEvent` if needed in future |
| Survey | `WebPage` | ✅ Auto (default) | No gap |
| Newsletter signup | `WebPage` | ✅ Auto (default) | No gap |
| About page | `AboutPage` | 🔧 Manual | Set `tx_maiseo_schema_type=AboutPage`; PageCollector provides `name`, `description`, `url`, `datePublished`, `dateModified` — all critical fields covered |
| Contact page | `ContactPage` | 🔧 Manual | Set `tx_maiseo_schema_type=ContactPage`; `contactType` and `telephone` are not auto-populated |
| Imprint (Impressum) | `WebPage` or `AboutPage` | ✅ Auto (default) or 🔧 Manual | No gap; `WebPage` default is acceptable; editors may optionally set `AboutPage` |
| Data protection policy | `WebPage` | ✅ Auto (default) | No gap |
| Accessibility statement | `WebPage` | ✅ Auto (default) | No gap |
| Disclaimer | `WebPage` | ✅ Auto (default) | No gap |

**Summary:**
- 14 of 22 functional page types: ✅ auto-covered (default `WebPage` + BreadcrumbList is appropriate)
- 6 of 22 functional page types: 🔧 manual `@type` assignment needed, domain-specific properties must be added via the editor
- 2 of 22 functional page types: ❌ recommended `@type` (`CollectionPage`, `JobPosting`) absent from the current vocabulary

**Recommended follow-up tasks:**

| Priority | Action |
|---|---|
| High | Add `JobPosting` type to `vocabulary.json` and TCA select options (see `mai_jobs` FEATURES.md §10) |
| ✅ Done (2026-05-24) | Implement `OrganizationCollector` reading from `config/sites/bgm-pulheim/settings.yaml` — fills `Organization.name`, `.url`, `.logo`; `address`, `telephone` added when present in site settings |
| Medium | Implement `FAQCollector` — reads `tx_maifaq_faq` records for the current page's storage PID and builds `FAQPage.mainEntity` array |
| Low | Add `CollectionPage`, `Review`, `AggregateRating`, `ProfilePage` to vocabulary.json |
| ✅ Done (2026-05-24) | Fix `BreadcrumbCollector` to use site router for slug-based item URLs |

## TCA Page Overrides

`mai_seo` adds an **SEO Overrides** tab to the page properties form (displayed for doktype 1 Standard and 4 Shortcut pages):

| Column | Type | Purpose |
|---|---|---|
| `tx_maiseo_og_title` | `input` (max 255) | Open Graph title (`og:title`); leave empty to use the page title |
| `tx_maiseo_og_description` | `text` | Open Graph description (`og:description`); leave empty to use the page description |
| `tx_maiseo_og_image` | `file` (max 1, images) | Open Graph image (`og:image`); overrides the default page image |
| `tx_maiseo_schema_type` | `selectSingle` (13 options) | Root `@type` for the JSON-LD graph; blank = auto (defaults to `WebPage`) |
| `tx_maiseo_structured_data` | custom `maiseoStructuredDataTree` renderType | Visual JSON-LD tree editor (see next section) |

The palette order is: `tx_maiseo_og_title`, `tx_maiseo_og_description`, `tx_maiseo_og_image`, `tx_maiseo_schema_type`.

## Open Graph & Twitter Card Meta Tags

`OpenGraphMetaTagGenerator` hooks into TYPO3's `generateMetaTags` SC_OPTION and emits `og:*` and `twitter:*` tags via the core `MetaTagManagerRegistry` (same API as `typo3/cms-seo`).

**Fallback chain:**

| Tag | Priority |
|---|---|
| `og:title` / `twitter:title` | `tx_maiseo_og_title` → `pages.title` → site `websiteTitle` |
| `og:description` / `twitter:description` | `tx_maiseo_og_description` → `pages.description` |
| `og:image` / `twitter:image` | `tx_maiseo_og_image` → `pages.media` (first image) |
| `og:type` | Mapped from `tx_maiseo_schema_type` (`Article` → `article`, `Person` → `profile`, default → `website`) |
| `og:url` | `pages.canonical_link` or current page URL |
| `og:site_name` | Language-specific `websiteTitle` or site config fallback |
| `twitter:card` | `summary_large_image` when an image is present, otherwise `summary` |

Canonical URL and hreflang tags are provided by the core `typo3/cms-seo` extension (`CanonicalGenerator`, `HrefLangGenerator`); no mai_seo override is required.

## Backend Structured Data Tree Editor

`StructuredDataTreeElement` is a custom TCA `renderType` backed by class
`Maispace\MaiSeo\Form\Element\StructuredDataTreeElement` registered in `Services.yaml` as a `backend.form.element` with identifier `maiseoStructuredDataTree`.

**Rendering flow:**

1. Fetches the current auto-generated graph via `StructuredDataService::getAutoDataForPage()`.
2. Builds the inline vocabulary config (`types: string[]`, `propertiesByType: {type: properties[]}`) from `VocabularyRegistry`.
3. Dispatches `StructuredDataEditorConfigEvent` so downstream extensions can add types/properties to the editor dropdown without touching the core registry.
4. Loads the ES module `@maispace/mai-seo/tree/structured-data-tree-editor.js` via `PageRenderer::loadJavaScriptModule()`.
5. Outputs a `<div data-auto="{autoJson}" data-config="{configJson}">` mount element and a hidden `<input>` for the stored override JSON.

**Stored value:** The hidden input holds only the **editor's override layer** (not the full merged graph). An empty string means "no overrides — use the auto-generated graph unchanged."

**Override behaviour:** To remove an auto-generated property from the frontend output, set its value to the string `__remove__` in the override JSON. `JsonMerge::deepMerge()` strips all `__remove__` sentinels from the final result.

## PSR-14 Events

| Event | Fired by | Purpose |
|---|---|---|
| `PageRecordSavedEvent` | `PageRecordSaveHook` (DataHandler `processDatamap_afterDatabaseOperations`) | Carries `pageUid`, `fieldArray`, `status ('new'|'update')`; `PageRecordSavedListener` invalidates the structured-data cache |
| `StructuredDataCollectionEvent` | `AutoGenerator::generateForPage()` | Carries `pageUid`, `pageRecord`, mutable graph; all registered `CollectorInterface` services receive this event; external listeners may also contribute graph data |
| `StructuredDataRenderEvent` | `AutoGenerator::generateForPage()` | Carries `pageUid`, mutable graph; fired after all collectors run; allows last-minute graph modifications before caching |
| `VocabularyLoadedEvent` | `VocabularyRegistry::ensureLoaded()` | Carries the `VocabularyRegistryInterface`; listeners call `registerType()` / `extendType()` to add custom schema.org types or extend existing property lists |
| `StructuredDataEditorConfigEvent` | `StructuredDataTreeElement::render()` | Carries `pageUid`, mutable editor config array; listeners call `addType(string, array)` to expose additional types in the backend tree editor |

### Adding a custom type via VocabularyLoadedEvent

```php
// In a downstream extension's event listener:
public function __invoke(VocabularyLoadedEvent $event): void
{
    $event->getRegistry()->registerType(new TypeDefinition(
        name: 'JobPosting',
        label: 'Job Posting',
        ancestors: ['Thing'],
        properties: ['title', 'description', 'datePosted', 'validThrough', 'hiringOrganization',
                     'jobLocation', 'employmentType', 'directApply'],
    ));
}
```

## Collector API

Any tagged DI service implementing `CollectorInterface` is auto-registered in `CollectorRegistry`.

```yaml
# Services.yaml — tag a new collector
Acme\MyExtension\StructuredData\Collector\MyCollector:
    tags:
        - name: 'maiseo.structured_data.collector'
```

```php
// CollectorInterface contract
interface CollectorInterface
{
    public function collect(StructuredDataCollectionEvent $event): void;
    public function supportedTypes(): array;  // ['*'] for all types, or ['Article', 'WebPage'] etc.
    public function priority(): int;           // higher runs first; PageCollector=100, BreadcrumbCollector=90
}
```

`CollectorRegistry::getCollectors()` sorts all registered collectors by `priority()` descending on every call (safe for re-ordering after DI wiring).

## TypoScript Configuration

Constants (auto-included via `ext_localconf.php`):

```typo3_typoscript
plugin.tx_maiseo {
    settings {
        structuredData {
            autoGenerate = 1              # 0 = disable auto-generation pipeline
            injectViaPageRenderer = 1     # 1 = inject <script> directly via PageRenderer
            defaultRootType = WebPage     # @type when tx_maiseo_schema_type is empty
            editorAllowedTypes =          # comma list to restrict editor type dropdown; empty = all
            showAutoNodes = 1             # 1 = show auto-generated nodes in tree editor
            maxNestingDepth = 5           # maximum nesting depth for the tree editor
        }
    }
}
```

The setup adds `StructuredDataProcessor` to `page.10.dataProcessing.200` as variable `structuredData`.

## Database Tables

`mai_seo` adds four columns to the `pages` table (no new tables):

| Column | SQL type | Purpose |
|---|---|---|
| `tx_maiseo_structured_data` | `mediumtext DEFAULT NULL` | Editor override JSON-LD (null or empty string = no override) |
| `tx_maiseo_og_title` | `varchar(255) DEFAULT '' NOT NULL` | Open Graph title override |
| `tx_maiseo_og_description` | `text` | Open Graph description override |
| `tx_maiseo_schema_type` | `varchar(64) DEFAULT '' NOT NULL` | Selected schema.org type; empty = auto |

Structured data is cached in TYPO3's `maiseo_structured_data` cache (Typo3DatabaseBackend, 24-hour default TTL, grouped under `pages` so a full page-cache clear also clears structured data).

## Architecture Constraints

- No custom database tables — all storage is via `pages` column additions.
- No mail dispatch, no SCSS, no Extbase plugin.
- `mai_seo` depends only on `typo3/cms-core`, `typo3/cms-seo`, `typo3/cms-redirects`, and `typo3/cms-backend`.
- Collectors must be stateless and synchronous — no HTTP calls, no database writes inside `collect()`.
- The stored override JSON holds **only overrides**, never the full merged graph — merging is always done at render time.
- The `__remove__` sentinel (`JsonMerge::REMOVE_SENTINEL`) is the only mechanism to remove auto-generated properties; setting a property to `null` or `''` does **not** remove it.
- `VocabularyRegistry` is a singleton; `ensureLoaded()` loads vocabulary exactly once per request; subsequent calls are no-ops.
- All schema.org types shown in the TCA `tx_maiseo_schema_type` select and the tree editor must also be present in `vocabulary.json` (or registered via `VocabularyLoadedEvent`) — otherwise the tree editor cannot offer property dropdowns for the type.
