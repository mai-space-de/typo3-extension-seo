.. _events:

PSR-14 Events
=============

Every rendering step dispatches PSR-14 events so that site packages and other
extensions can modify or suppress output without patching this extension.

Event overview
--------------

+-------------------------------+----------------------------------------------+------+
| Event class                   | When dispatched                              | Can  |
|                               |                                              | veto |
+===============================+==============================================+======+
| ``BeforeJsonLdRenderedEvent`` | After schema assembly, before rendering      | yes  |
+-------------------------------+----------------------------------------------+------+
| ``AfterJsonLdRenderedEvent``  | After ``<script>`` tag is built              | no   |
+-------------------------------+----------------------------------------------+------+
| ``BeforeOpenGraphRenderedEvent`` | After OG properties assembled, before     | yes  |
|                               | meta tags are added                          |      |
+-------------------------------+----------------------------------------------+------+
| ``AfterOpenGraphRenderedEvent`` | After all meta tags are built (read-only)  | no   |
+-------------------------------+----------------------------------------------+------+
| ``BeforeCanonicalRenderedEvent`` | After URL resolution, before tag is       | yes  |
|                               | rendered                                     |      |
+-------------------------------+----------------------------------------------+------+
| ``AfterCanonicalRenderedEvent`` | After ``<link rel="canonical">`` tag is   | no   |
|                               | built                                        |      |
+-------------------------------+----------------------------------------------+------+
| ``BeforeRobotsRenderedEvent`` | After directives string is built, before     | yes  |
|                               | tag is rendered                              |      |
+-------------------------------+----------------------------------------------+------+
| ``AfterRobotsRenderedEvent``  | After ``<meta name="robots">`` tag is built  | no   |
+-------------------------------+----------------------------------------------+------+

Registering a listener
-----------------------

Register listeners in your site package's ``Configuration/Services.yaml``:

.. code-block:: yaml

    # EXT:my_sitepackage/Configuration/Services.yaml
    services:
      MyVendor\MySitepackage\EventListener\ModifyJsonLd:
        tags:
          - name: event.listener
            identifier: 'my-sitepackage/modify-json-ld'
            event: Maispace\MaispacesSeo\Event\BeforeJsonLdRenderedEvent

      MyVendor\MySitepackage\EventListener\DisableCanonicalOnSearch:
        tags:
          - name: event.listener
            identifier: 'my-sitepackage/disable-canonical-on-search'
            event: Maispace\MaispacesSeo\Event\BeforeCanonicalRenderedEvent

Example: modify JSON-LD schema
-------------------------------

.. code-block:: php

    use Maispace\MaispacesSeo\Event\BeforeJsonLdRenderedEvent;

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

Example: suppress canonical on specific pages
----------------------------------------------

.. code-block:: php

    use Maispace\MaispacesSeo\Event\BeforeCanonicalRenderedEvent;

    final class DisableCanonicalOnSearch
    {
        public function __invoke(BeforeCanonicalRenderedEvent $event): void
        {
            // Do not emit a canonical tag for search result pages
            $page = $event->getPageRecord();
            if (($page['doktype'] ?? 0) === 7) { // shortcut/special type
                $event->disable();
            }
        }
    }

Example: override robots directives programmatically
-----------------------------------------------------

.. code-block:: php

    use Maispace\MaispacesSeo\Event\BeforeRobotsRenderedEvent;

    final class ForceNocacheRobots
    {
        public function __invoke(BeforeRobotsRenderedEvent $event): void
        {
            // Append noarchive to whatever directives were resolved
            $current = $event->getDirectives();
            if (!str_contains($current, 'noarchive')) {
                $event->setDirectives($current . ', noarchive');
            }
        }
    }

Example: replace canonical URL
--------------------------------

.. code-block:: php

    use Maispace\MaispacesSeo\Event\BeforeCanonicalRenderedEvent;

    final class RewriteCanonical
    {
        public function __invoke(BeforeCanonicalRenderedEvent $event): void
        {
            $url = $event->getCanonicalUrl();
            // Strip query string from canonical
            $clean = strtok($url, '?') ?: $url;
            $event->setCanonicalUrl($clean);
        }
    }
