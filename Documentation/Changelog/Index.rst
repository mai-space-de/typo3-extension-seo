.. _changelog:

Changelog
=========

1.2.0 (unreleased)
------------------

New features
~~~~~~~~~~~~

*  **Meta description ViewHelper** — ``<mai:seo.metaDescription>`` renders
   ``<meta name="description" content="...">`` in the page ``<head>``. The
   description is resolved from a new ``tx_maispace_seo_meta_description``
   override field first, falling back to the TYPO3 core ``description`` field
   and finally the page ``abstract`` field.
*  **AI agent robots ViewHelper** — ``<mai:seo.aiRobots>`` emits dedicated
   per-bot ``<meta name="BotName" content="noindex">`` tags for a configurable
   list of AI crawlers (GPTBot, ClaudeBot, Google-Extended, and more), controlled
   by a new ``tx_maispace_seo_ai_noindex`` page checkbox. These tags do not
   interfere with the standard ``<meta name="robots">`` tag.
*  **PSR-14 events** for both new ViewHelpers:
   ``BeforeMetaDescriptionRenderedEvent``, ``AfterMetaDescriptionRenderedEvent``,
   ``BeforeAiRobotsRenderedEvent``, ``AfterAiRobotsRenderedEvent``.
*  **Fixed** ``AfterOpenGraphRenderedEvent`` — added missing ``setProperties()``
   method so listeners can modify the final Open Graph property list, consistent
   with all other ``After*`` events.

Database changes
~~~~~~~~~~~~~~~~

Two new columns added to ``pages``:

*  ``tx_maispace_seo_meta_description`` (text)
*  ``tx_maispace_seo_ai_noindex`` (smallint)

TypoScript changes
~~~~~~~~~~~~~~~~~~

Two new configuration blocks under ``plugin.tx_maispace_seo``:

.. code-block:: typoscript

    metaDescription {
        enable = 1
    }
    aiRobots {
        enable = 1
        bots = GPTBot, OAI-SearchBot, ClaudeBot, Google-Extended, PerplexityBot, CCBot, Bytespider, Amazonbot
    }

1.1.0 (unreleased)
------------------

New features
~~~~~~~~~~~~

*  **Canonical URL ViewHelper** — ``<mai:seo.canonical>`` renders
   ``<link rel="canonical" href="...">`` in the page ``<head>``. The URL is
   resolved from a new ``tx_maispace_seo_canonical_url`` page field first,
   falling back to the TYPO3 core ``canonical_link`` field.
*  **Robots meta tag ViewHelper** — ``<mai:seo.robots>`` renders
   ``<meta name="robots" content="...">`` with fine-grained per-page control
   via three new checkboxes: ``noindex``, ``nofollow``, ``noarchive``.
*  **og:url population** — ``<mai:seo.openGraph>`` now automatically populates
   ``og:url`` from the resolved canonical URL instead of leaving it empty.
*  **PSR-14 events** for both new ViewHelpers:
   ``BeforeCanonicalRenderedEvent``, ``AfterCanonicalRenderedEvent``,
   ``BeforeRobotsRenderedEvent``, ``AfterRobotsRenderedEvent``.
*  **RST documentation** — a full ``Documentation/`` tree following the TYPO3
   documentation standard (Introduction, Installation, Configuration, Usage,
   Events, Changelog).

Database changes
~~~~~~~~~~~~~~~~

Four new columns added to ``pages``:

*  ``tx_maispace_seo_canonical_url`` (varchar 2048)
*  ``tx_maispace_seo_robots_noindex`` (smallint)
*  ``tx_maispace_seo_robots_nofollow`` (smallint)
*  ``tx_maispace_seo_robots_noarchive`` (smallint)

TypoScript changes
~~~~~~~~~~~~~~~~~~

Two new configuration blocks under ``plugin.tx_maispace_seo``:

.. code-block:: typoscript

    canonical {
        enable = 1
    }
    robots {
        enable = 1
    }

1.0.0
-----

Initial release:

*  JSON-LD structured data (10 schema types) with custom JSON-LD merge
*  Open Graph and Twitter Card meta tags
*  Page property TCA fields (14 columns)
*  PSR-14 events (BeforeJsonLdRenderedEvent, AfterJsonLdRenderedEvent,
   BeforeOpenGraphRenderedEvent, AfterOpenGraphRenderedEvent)
*  Backend module Web > SEO (overview + statistics)
*  Fluid ViewHelpers ``<mai:seo.jsonLd>`` and ``<mai:seo.openGraph>``
