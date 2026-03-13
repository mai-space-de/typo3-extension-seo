.. _configuration:

Configuration
=============

TypoScript reference
--------------------

All settings live under ``plugin.tx_maispace_seo``:

.. code-block:: typoscript

    plugin.tx_maispace_seo {

        # JSON-LD / structured data
        jsonLd {
            enable = 1              # 0 to disable JSON-LD globally
            defaultType = WebPage   # fallback schema @type when none set on page
            organizationName =      # publisher.name in every schema
            organizationUrl =       # publisher.url
            organizationLogo =      # EXT: or public path — publisher.logo
        }

        # Open Graph meta tags
        openGraph {
            enable = 1             # 0 to disable Open Graph globally
            siteName =             # og:site_name on every page
            defaultImage =         # EXT: or public path — fallback og:image
            twitter = 1            # 0 to suppress twitter:card tags globally
            twitterSite =          # @handle for twitter:site
        }

        # Canonical URL (<link rel="canonical">)
        canonical {
            enable = 1             # 0 to disable canonical tag globally
        }

        # Robots meta tag (<meta name="robots">)
        robots {
            enable = 1             # 0 to disable robots meta tag globally
        }

        # Meta description (<meta name="description">)
        metaDescription {
            enable = 1             # 0 to disable meta description tag globally
        }

        # AI crawler robots tags (<meta name="BotName" content="noindex">)
        aiRobots {
            enable = 1             # 0 to disable AI robots tags globally
            bots = GPTBot, OAI-SearchBot, ClaudeBot, Google-Extended, PerplexityBot, CCBot, Bytespider, Amazonbot
                                   # comma-separated list of AI bot names to target
        }
    }

Page properties — SEO tab
--------------------------

The extension adds a dedicated **SEO** tab with three palettes to every page
type in the TYPO3 backend.

JSON-LD / Structured Data palette
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

+--------------------+-------------------------------------------------------------------+
| Field              | Description                                                       |
+====================+===================================================================+
| Schema type        | ``WebPage``, ``Article``, ``Product``, ``FAQPage``, …            |
+--------------------+-------------------------------------------------------------------+
| Name override      | Overrides ``name`` (defaults to page title)                       |
+--------------------+-------------------------------------------------------------------+
| Description        | Overrides ``description`` (defaults to page abstract)             |
+--------------------+-------------------------------------------------------------------+
| Image              | FAL reference used as ``image`` in the schema                     |
+--------------------+-------------------------------------------------------------------+
| Author             | Free-text author name (rendered as ``Person`` sub-schema)         |
+--------------------+-------------------------------------------------------------------+
| Date published     | Used as ``datePublished`` (ISO 8601)                              |
+--------------------+-------------------------------------------------------------------+
| Date modified      | Used as ``dateModified`` (ISO 8601)                               |
+--------------------+-------------------------------------------------------------------+
| Custom JSON-LD     | Raw JSON object merged into the generated schema                  |
+--------------------+-------------------------------------------------------------------+

Open Graph palette
~~~~~~~~~~~~~~~~~~

+--------------------+-------------------------------------------------------------------+
| Field              | Description                                                       |
+====================+===================================================================+
| OG title           | Overrides ``og:title`` (defaults to page title)                   |
+--------------------+-------------------------------------------------------------------+
| OG description     | Overrides ``og:description`` (defaults to abstract)               |
+--------------------+-------------------------------------------------------------------+
| OG image           | FAL reference for ``og:image``                                    |
+--------------------+-------------------------------------------------------------------+
| OG type            | ``website``, ``article``, ``product``, …                          |
+--------------------+-------------------------------------------------------------------+
| Twitter card       | ``summary``, ``summary_large_image``, ``app``, ``player``         |
+--------------------+-------------------------------------------------------------------+
| Twitter image      | Optional separate image for ``twitter:image``                     |
+--------------------+-------------------------------------------------------------------+

Advanced / Crawling palette
~~~~~~~~~~~~~~~~~~~~~~~~~~~

+-------------------------------+-------------------------------------------------------------------+
| Field                         | Description                                                       |
+===============================+===================================================================+
| Meta description override     | Overrides ``<meta name="description">`` (falls back to TYPO3      |
|                               | core ``description`` field, then ``abstract``)                    |
+-------------------------------+-------------------------------------------------------------------+
| Canonical URL override        | Overrides ``<link rel="canonical">`` and ``og:url``               |
|                               | (falls back to TYPO3 core ``canonical_link`` when empty)          |
+-------------------------------+-------------------------------------------------------------------+
| noindex                       | Adds ``noindex`` to ``<meta name="robots">``                      |
+-------------------------------+-------------------------------------------------------------------+
| nofollow                      | Adds ``nofollow`` to ``<meta name="robots">``                     |
+-------------------------------+-------------------------------------------------------------------+
| noarchive                     | Adds ``noarchive`` to ``<meta name="robots">``                    |
+-------------------------------+-------------------------------------------------------------------+
| Block AI crawlers (noindex)   | Emits ``<meta name="BotName" content="noindex">`` for every       |
|                               | AI crawler listed in ``aiRobots.bots``                            |
+-------------------------------+-------------------------------------------------------------------+

Meta description resolution
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

When the meta description tag is enabled (``metaDescription.enable = 1``), the
ViewHelper ``<mai:seo.metaDescription>`` resolves the description value in this
priority order:

1. ``tx_maispace_seo_meta_description`` — custom override field on the page
2. ``description`` — TYPO3 core meta description field
3. ``abstract`` — TYPO3 page abstract field
4. Empty — no ``<meta name="description">`` tag is rendered

Robots logic
^^^^^^^^^^^^

When the robots tag is enabled (``robots.enable = 1``), the ViewHelper
``<mai:seo.robots>`` always emits a ``<meta name="robots">`` tag. The content
is built as follows:

*  ``index`` or ``noindex`` — controlled by the *noindex* checkbox
*  ``follow`` or ``nofollow`` — controlled by the *nofollow* checkbox
*  ``noarchive`` — added only when the checkbox is checked; omitted otherwise

Example output when only *noindex* is checked:

.. code-block:: html

    <meta name="robots" content="noindex, follow">

AI crawler control
^^^^^^^^^^^^^^^^^^

When ``aiRobots.enable = 1`` and the *Block AI crawlers* checkbox is set on a
page, ``<mai:seo.aiRobots>`` emits a separate ``<meta name="BotName" content="noindex">``
tag for every bot in the ``aiRobots.bots`` TypoScript list. These per-bot tags
do **not** modify the standard ``<meta name="robots">`` tag.

Default bots: ``GPTBot``, ``OAI-SearchBot``, ``ClaudeBot``, ``Google-Extended``,
``PerplexityBot``, ``CCBot``, ``Bytespider``, ``Amazonbot``.

To customise the list via TypoScript:

.. code-block:: typoscript

    plugin.tx_maispace_seo.aiRobots.bots = GPTBot, ClaudeBot, MyCustomBot

Canonical URL resolution
^^^^^^^^^^^^^^^^^^^^^^^^

The canonical URL is resolved in this priority order:

1. ``tx_maispace_seo_canonical_url`` — custom override field on the page
2. ``canonical_link`` — TYPO3 core field (set by EXT:seo or manually)
3. Empty — no ``<link rel="canonical">`` tag is rendered

The resolved canonical URL is also passed to ``og:url`` when the Open Graph
ViewHelper is used.
