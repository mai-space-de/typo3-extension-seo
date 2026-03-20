# maispace/seo — SEO Helpers and Tools for TYPO3

[![CI](https://github.com/mai-space-de/typo3-extension-seo/actions/workflows/ci.yml/badge.svg)](https://github.com/mai-space-de/typo3-extension-seo/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue)](https://www.php.net/)
[![TYPO3](https://img.shields.io/badge/TYPO3-13.4%20LTS-orange)](https://typo3.org/)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html)

A TYPO3 extension that adds structured data (JSON-LD), Open Graph meta tags, canonical URL rendering, and robots meta tag control to page properties. Provides Fluid ViewHelpers for frontend output and a backend module for SEO statistics.

**Requires:** TYPO3 13.4 LTS · PHP 8.2+

---

## Features at a glance

| Feature | API |
|---|---|
| JSON-LD structured data fields on pages | Backend page properties tab |
| Open Graph meta fields on pages | Backend page properties tab |
| Canonical URL override per page | Backend page properties tab |
| Robots meta tag control per page (noindex / nofollow / noarchive) | Backend page properties tab |
| Meta description override per page | Backend page properties tab |
| AI crawler blocking per page | Backend page properties tab |
| Output structured data in Fluid templates | `<mai:seo.jsonLd>` |
| Output Open Graph meta tags in Fluid templates | `<mai:seo.openGraph>` |
| Output canonical link tag | `<mai:seo.canonical>` |
| Output robots meta tag | `<mai:seo.robots>` |
| Output meta description tag | `<mai:seo.metaDescription>` |
| Output per-bot AI crawler noindex tags | `<mai:seo.aiRobots>` |
| PSR-14 events to control/modify output | `Classes/Event/` |
| Backend module for SEO settings overview | `Web > SEO` module |
| SEO statistics per page | Backend module dashboard |
| GDPR-compliant — no external data transmission | Built-in |

---

## Installation

```bash
composer require maispace/seo
```

Include the TypoScript setup in your site package:

```typoscript
@import 'EXT:maispace_seo/Configuration/TypoScript/setup.typoscript'
```

No extension manager configuration required.

---

## Page Properties

The extension adds a dedicated **SEO** tab to the page properties form in the TYPO3 backend. Editors can fill in the following fields per page:

**JSON-LD / Structured Data**

| Field | Description |
|---|---|
| Schema type | `WebPage`, `Article`, `Product`, `FAQPage`, … |
| Name | Override for `name` property (defaults to page title) |
| Description | Override for `description` (defaults to page abstract) |
| Image | FAL reference used as `image` in the schema |
| Author | Free-text or relation to a `Person` schema |
| Date published | Used as `datePublished` |
| Date modified | Used as `dateModified` (auto-updated on page save) |
| Custom JSON-LD | Raw JSON block merged into the generated schema |

**Open Graph**

| Field | Description |
|---|---|
| OG title | Overrides `og:title` (defaults to page title) |
| OG description | Overrides `og:description` (defaults to abstract) |
| OG image | FAL reference for `og:image` |
| OG type | `website`, `article`, `product`, … |
| Twitter card | `summary`, `summary_large_image`, `app`, `player` |
| Twitter image | Optional separate image for Twitter |

**Advanced / Crawling**

| Field | Description |
|---|---|
| Meta description override | Overrides `<meta name="description">`; falls back to TYPO3 core `description` field, then `abstract` |
| Canonical URL override | Overrides `<link rel="canonical">` and `og:url`; falls back to TYPO3 core `canonical_link` |
| noindex | Adds `noindex` to `<meta name="robots">` |
| nofollow | Adds `nofollow` to `<meta name="robots">` |
| noarchive | Adds `noarchive` to `<meta name="robots">` |
| Block AI crawlers | Emits `<meta name="BotName" content="noindex">` for every configured AI crawler |

---

## ViewHelpers

### `<mai:seo.jsonLd>` — render structured data

Add the ViewHelper once to your layout's `<head>` section. It renders the JSON-LD block for the current page as an inline `<script type="application/ld+json">`.

```html
<!-- Render JSON-LD for the current page (auto-detected from context) -->
<mai:seo.jsonLd />

<!-- Render for an explicit page UID -->
<mai:seo.jsonLd pageUid="{pageUid}" />

<!-- Suppress output entirely on this page (e.g. search results) -->
<mai:seo.jsonLd enabled="false" />
```

### `<mai:seo.openGraph>` — render Open Graph meta tags

```html
<!-- Render all og: and twitter: meta tags for the current page -->
<mai:seo.openGraph />

<!-- Render for an explicit page UID -->
<mai:seo.openGraph pageUid="{pageUid}" />

<!-- Restrict to og: tags only (no twitter:) -->
<mai:seo.openGraph twitter="false" />
```

The `og:url` is populated automatically from the canonical URL field.

### `<mai:seo.canonical>` — render canonical link tag

```html
<!-- Render <link rel="canonical"> for the current page -->
<mai:seo.canonical />

<!-- Render for an explicit page UID -->
<mai:seo.canonical pageUid="{pageUid}" />

<!-- Suppress canonical output on this page -->
<mai:seo.canonical enabled="false" />
```

URL priority: custom `tx_maiseo_canonical_url` field → TYPO3 core `canonical_link` field → nothing rendered.

### `<mai:seo.robots>` — render robots meta tag

```html
<!-- Render <meta name="robots"> for the current page -->
<mai:seo.robots />

<!-- Render for an explicit page UID -->
<mai:seo.robots pageUid="{pageUid}" />

<!-- Suppress robots output on this page -->
<mai:seo.robots enabled="false" />
```

Generates `index`/`noindex`, `follow`/`nofollow`, and optionally `noarchive` from the page checkboxes.

### `<mai:seo.metaDescription>` — render meta description

```html
<!-- Render <meta name="description"> for the current page -->
<mai:seo.metaDescription />

<!-- Render for an explicit page UID -->
<mai:seo.metaDescription pageUid="{pageUid}" />

<!-- Suppress meta description output on this page -->
<mai:seo.metaDescription enabled="false" />
```

Description priority: custom `tx_maiseo_meta_description` field → TYPO3 core `description` field → `abstract` field → nothing rendered.

### `<mai:seo.aiRobots>` — render AI crawler meta tags

```html
<!-- Emit per-bot noindex tags when the page checkbox is set -->
<mai:seo.aiRobots />

<!-- Render for an explicit page UID -->
<mai:seo.aiRobots pageUid="{pageUid}" />

<!-- Suppress AI robots output on this page -->
<mai:seo.aiRobots enabled="false" />
```

When the *Block AI crawlers* checkbox is checked, emits `<meta name="BotName" content="noindex">` for each bot in the `aiRobots.bots` TypoScript list (default: GPTBot, OAI-SearchBot, ClaudeBot, Google-Extended, PerplexityBot, CCBot, Bytespider, Amazonbot). These tags do not modify the standard `<meta name="robots">` tag.

All ViewHelpers inject tags via TYPO3's `PageRenderer` — they always land in `<head>` regardless of where the ViewHelper is called in the template.

### Recommended layout snippet

```html
<mai:seo.metaDescription />
<mai:seo.canonical />
<mai:seo.robots />
<mai:seo.aiRobots />
<mai:seo.jsonLd />
<mai:seo.openGraph />
```

---

## PSR-14 Events

Hook into the rendering pipeline by registering listeners in your site package's `Configuration/Services.yaml`:

| Event | When |
|---|---|
| `BeforeJsonLdRenderedEvent` | Before JSON-LD is assembled — modify schema data or veto output |
| `AfterJsonLdRenderedEvent` | After JSON-LD script tag is built — inspect or replace the output string |
| `BeforeOpenGraphRenderedEvent` | Before OG tags are assembled — modify properties or veto output |
| `AfterOpenGraphRenderedEvent` | After all OG/Twitter meta tags are built — modify properties |
| `BeforeCanonicalRenderedEvent` | After URL resolution — modify canonical URL or veto output |
| `AfterCanonicalRenderedEvent` | After `<link rel="canonical">` tag is built |
| `BeforeRobotsRenderedEvent` | After directives string is built — modify directives or veto output |
| `AfterRobotsRenderedEvent` | After `<meta name="robots">` tag is built |
| `BeforeMetaDescriptionRenderedEvent` | After description is resolved — modify text or veto output |
| `AfterMetaDescriptionRenderedEvent` | After `<meta name="description">` tag is built |
| `BeforeAiRobotsRenderedEvent` | After AI bot tags are assembled — modify tags or veto output |
| `AfterAiRobotsRenderedEvent` | After AI crawler meta tags are built — modify tags |

Example listener registration:

```yaml
# EXT:my_sitepackage/Configuration/Services.yaml
services:
  MyVendor\MySitepackage\EventListener\ModifyJsonLd:
    tags:
      - name: event.listener
        identifier: 'my-sitepackage/modify-json-ld'
        event: Maispace\MaiSeo\Event\BeforeJsonLdRenderedEvent
```

Example listener:

```php
use Maispace\MaiSeo\Event\BeforeJsonLdRenderedEvent;

final class ModifyJsonLd
{
    public function __invoke(BeforeJsonLdRenderedEvent $event): void
    {
        $schema = $event->getSchema();
        $schema['publisher'] = [
            '@type' => 'Organization',
            'name'  => 'My Company',
            'url'   => 'https://example.com',
        ];
        $event->setSchema($schema);
    }
}
```

---

## Backend Module

The **SEO** backend module (`Web > SEO`) gives administrators an overview of the SEO state across the site:

- **Settings overview** — list all pages with their JSON-LD schema type, OG image, and title fill status
- **Missing fields** — pages flagged where title, description, or OG image are empty
- **Statistics** — charts showing schema type distribution and OG image coverage per site tree level
- **Bulk edit** — jump to page properties for any flagged page directly from the module

The module respects backend user permissions — editors only see pages within their mount points.

---

## TypoScript Configuration

```typoscript
plugin.tx_maispace_seo {
    jsonLd {
        enable = 1
        defaultType = WebPage      # fallback schema type when none is set on the page
        organizationName =         # injected as publisher.name in all schemas
        organizationUrl =          # injected as publisher.url
        organizationLogo =         # EXT: or public path to logo image
    }
    openGraph {
        enable = 1
        siteName =                 # injected as og:site_name on all pages
        defaultImage =             # EXT: or public path — fallback when no OG image is set
        twitter = 1                # 0 to suppress twitter:card tags globally
        twitterSite =              # @handle for twitter:site
    }
    canonical {
        enable = 1                 # 0 to suppress <link rel="canonical"> globally
    }
    robots {
        enable = 1                 # 0 to suppress <meta name="robots"> globally
    }
    metaDescription {
        enable = 1                 # 0 to suppress <meta name="description"> globally
    }
    aiRobots {
        enable = 1                 # 0 to suppress AI crawler meta tags globally
        bots = GPTBot, OAI-SearchBot, ClaudeBot, Google-Extended, PerplexityBot, CCBot, Bytespider, Amazonbot
                                   # comma-separated list of AI bot names to target
    }
}
```

---

## Development

### Running tests

```bash
composer install
composer test
```

Or verbose:

```bash
vendor/bin/phpunit --configuration phpunit.xml.dist --testdox
```

**Test structure:**

| File | What it tests |
|---|---|
| `Tests/Unit/Service/JsonLdServiceTest.php` | Schema assembly, field merging, custom JSON block |
| `Tests/Unit/Service/OpenGraphServiceTest.php` | OG/Twitter tag assembly, image resolution, fallbacks |
| `Tests/Unit/Service/CanonicalServiceTest.php` | Canonical URL resolution, tag rendering, event dispatching |
| `Tests/Unit/Service/RobotsServiceTest.php` | Robots directives assembly, tag rendering, event dispatching |
| `Tests/Unit/Service/MetaDescriptionServiceTest.php` | Description resolution, tag rendering, event dispatching |
| `Tests/Unit/Service/AiRobotsServiceTest.php` | AI bot tag assembly, configurable bot list, event dispatching |
| `Tests/Unit/ViewHelper/JsonLdViewHelperTest.php` | ViewHelper argument handling, PageRenderer delegation |
| `Tests/Unit/ViewHelper/OpenGraphViewHelperTest.php` | ViewHelper argument handling, veto/skip logic |
| `Tests/Unit/ViewHelper/MetaDescriptionViewHelperTest.php` | ViewHelper argument handling, PageRenderer delegation |
| `Tests/Unit/ViewHelper/AiRobotsViewHelperTest.php` | ViewHelper argument handling, per-bot tag delegation |

All tests are pure unit tests — no database, no TYPO3 installation required.

### CI

| Job | What it checks |
|---|---|
| `composer-validate` | `composer.json` is valid and well-formed |
| `unit-tests` | PHPUnit suite across PHP 8.2 / 8.3 × TYPO3 13.4 |
| `static-analysis` | PHPStan (`phpstan.neon`, level max) |
| `code-style` | EditorConfig + PHP-CS-Fixer |
| `typoscript-lint` | TypoScript style/structure |

---

## License

GPL-2.0-or-later
