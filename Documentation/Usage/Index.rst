.. _usage:

Usage
=====

All ViewHelpers are available under the ``mai`` Fluid namespace, which the
extension registers automatically via ``ext_localconf.php``.

ViewHelpers overview
--------------------

+---------------------------+-----------------------------------------------------+
| ViewHelper                | Purpose                                             |
+===========================+=====================================================+
| ``<mai:seo.jsonLd>``      | Render JSON-LD structured data in ``<head>``        |
+---------------------------+-----------------------------------------------------+
| ``<mai:seo.openGraph>``   | Render Open Graph (+ Twitter) meta tags             |
+---------------------------+-----------------------------------------------------+
| ``<mai:seo.canonical>``   | Render ``<link rel="canonical">``                   |
+---------------------------+-----------------------------------------------------+
| ``<mai:seo.robots>``      | Render ``<meta name="robots">``                     |
+---------------------------+-----------------------------------------------------+

All ViewHelpers inject their output into TYPO3's ``PageRenderer`` and therefore
always appear in ``<head>`` regardless of where in the template tree they are
called.

``<mai:seo.jsonLd>`` — JSON-LD structured data
-----------------------------------------------

.. code-block:: html

    <!-- Render JSON-LD for the current page (auto-detected from request context) -->
    <mai:seo.jsonLd />

    <!-- Render for an explicit page UID -->
    <mai:seo.jsonLd pageUid="{pageUid}" />

    <!-- Suppress output entirely on this page (e.g. search-results page) -->
    <mai:seo.jsonLd enabled="false" />

Output example:

.. code-block:: html

    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Article",
        "name": "My Blog Post",
        "description": "A short abstract.",
        "datePublished": "2024-06-15T12:00:00+00:00",
        "author": {
            "@type": "Person",
            "name": "Jane Doe"
        },
        "publisher": {
            "@type": "Organization",
            "name": "Acme Corp",
            "url": "https://acme.example"
        }
    }
    </script>

``<mai:seo.openGraph>`` — Open Graph and Twitter meta tags
----------------------------------------------------------

.. code-block:: html

    <!-- Render all og: and twitter: meta tags for the current page -->
    <mai:seo.openGraph />

    <!-- Render for an explicit page UID -->
    <mai:seo.openGraph pageUid="{pageUid}" />

    <!-- Suppress twitter: tags (og: tags are still rendered) -->
    <mai:seo.openGraph twitter="false" />

    <!-- Suppress all Open Graph output -->
    <mai:seo.openGraph enabled="false" />

The ``og:url`` property is populated automatically from the canonical URL field
(see :ref:`configuration`).

``<mai:seo.canonical>`` — canonical link tag
--------------------------------------------

.. code-block:: html

    <!-- Render <link rel="canonical"> for the current page -->
    <mai:seo.canonical />

    <!-- Render for an explicit page UID -->
    <mai:seo.canonical pageUid="{pageUid}" />

    <!-- Suppress canonical output on this page -->
    <mai:seo.canonical enabled="false" />

The canonical URL is resolved from (in priority order):

1. ``tx_maispace_seo_canonical_url`` — the custom override field
2. ``canonical_link`` — the TYPO3 core field
3. Nothing is rendered when both are empty

Output example:

.. code-block:: html

    <link rel="canonical" href="https://example.com/my-page">

``<mai:seo.robots>`` — robots meta tag
--------------------------------------

.. code-block:: html

    <!-- Render <meta name="robots"> for the current page -->
    <mai:seo.robots />

    <!-- Render for an explicit page UID -->
    <mai:seo.robots pageUid="{pageUid}" />

    <!-- Suppress robots output on this page -->
    <mai:seo.robots enabled="false" />

The robots directives are built from the page checkboxes. A page with only the
*noindex* checkbox checked produces:

.. code-block:: html

    <meta name="robots" content="noindex, follow">

A fully restricted page (noindex + nofollow + noarchive):

.. code-block:: html

    <meta name="robots" content="noindex, nofollow, noarchive">

Recommended layout template
----------------------------

Add all four ViewHelpers once to your Fluid layout file's ``<head>`` section:

.. code-block:: html

    <html
        xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
        xmlns:mai="http://typo3.org/ns/Maispace/MaispacesSeo/ViewHelpers"
        data-namespace-typo3-fluid="true"
    >
    <f:be.pageRenderer />
    <f:section name="HtmlHead">
        <mai:seo.canonical />
        <mai:seo.robots />
        <mai:seo.jsonLd />
        <mai:seo.openGraph />
    </f:section>

Backend module
--------------

The **Web > SEO** backend module gives administrators a site-wide overview:

*  **Settings overview** — all pages with JSON-LD schema type, OG image status,
   and title completeness
*  **Statistics** — schema type distribution chart and OG image coverage
   percentage
*  **Quick edit links** — jump directly to the page properties form for any
   page flagged as incomplete

The module respects backend user permissions — editors only see pages within
their assigned mount points.
