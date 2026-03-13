.. _introduction:

Introduction
============

What does it do?
----------------

**maispace/seo** is a TYPO3 extension that covers the most important on-page
SEO signals without requiring any external services or data transmission. It
is built for TYPO3 13.4 LTS and PHP 8.2+.

Features at a glance
--------------------

+--------------------------------------------+----------------------------------------------+
| Feature                                    | API                                          |
+============================================+==============================================+
| JSON-LD structured data fields on pages    | Backend page properties (SEO tab)            |
+--------------------------------------------+----------------------------------------------+
| Open Graph meta fields on pages            | Backend page properties (SEO tab)            |
+--------------------------------------------+----------------------------------------------+
| Canonical URL override per page            | Backend page properties (SEO tab)            |
+--------------------------------------------+----------------------------------------------+
| Robots meta tag control per page           | Backend page properties (SEO tab)            |
+--------------------------------------------+----------------------------------------------+
| Output structured data in Fluid templates  | ``<mai:seo.jsonLd>``                         |
+--------------------------------------------+----------------------------------------------+
| Output Open Graph meta tags                | ``<mai:seo.openGraph>``                      |
+--------------------------------------------+----------------------------------------------+
| Output canonical link tag                  | ``<mai:seo.canonical>``                      |
+--------------------------------------------+----------------------------------------------+
| Output robots meta tag                     | ``<mai:seo.robots>``                         |
+--------------------------------------------+----------------------------------------------+
| PSR-14 events to control/modify output     | ``Classes/Event/``                           |
+--------------------------------------------+----------------------------------------------+
| Backend module — SEO settings overview     | ``Web > SEO`` module                         |
+--------------------------------------------+----------------------------------------------+
| SEO statistics per page                    | Backend module dashboard                     |
+--------------------------------------------+----------------------------------------------+
| GDPR-compliant — no external transmission  | Built-in                                     |
+--------------------------------------------+----------------------------------------------+

Why use this extension?
-----------------------

TYPO3 ships basic SEO support via ``EXT:seo``. The ``maispace/seo`` extension
complements it with:

*  Granular **JSON-LD / structured data** control (10 schema types, custom
   raw JSON merge, publisher organisation from TypoScript)
*  Per-page **Open Graph** fields (type, title, description, image) with
   automatic Twitter Card generation
*  **Canonical URL override** field that takes precedence over the TYPO3 core
   ``canonical_link`` field, with propagation to ``og:url``
*  Fine-grained **robots meta tag** control: ``noindex``, ``nofollow``, and
   ``noarchive`` checkboxes independent of the TYPO3 core flags
*  PSR-14 **events** for each rendering step so site packages can customise
   output without patching the extension
*  A **backend module** (Web > SEO) that shows a site-wide overview of SEO
   completeness and schema type distribution
