.. _changelog:

Changelog
=========

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
