.. _installation:

Installation
============

Composer install
----------------

.. code-block:: bash

    composer require maispace/seo

Include TypoScript
------------------

Add the bundled TypoScript setup to your site package. The easiest way is via
an ``@import`` statement in your TypoScript root template:

.. code-block:: typoscript

    @import 'EXT:maispace_seo/Configuration/TypoScript/setup.typoscript'

Database update
---------------

After installing the extension, run the **Database Analyser** in the TYPO3
Install Tool (or use the ``typo3 database:updateschema`` CLI command) to create
the new ``pages`` columns:

.. code-block:: bash

    vendor/bin/typo3 database:updateschema

New columns added to ``pages``
-------------------------------

+--------------------------------------------+-----------------+----------------------------------------------+
| Column                                     | Type            | Description                                  |
+============================================+=================+==============================================+
| ``tx_maispace_seo_jsonld_type``            | varchar(100)    | JSON-LD schema type                          |
+--------------------------------------------+-----------------+----------------------------------------------+
| ``tx_maispace_seo_jsonld_name``            | varchar(255)    | JSON-LD name override                        |
+--------------------------------------------+-----------------+----------------------------------------------+
| ``tx_maispace_seo_jsonld_description``     | text            | JSON-LD description override                 |
+--------------------------------------------+-----------------+----------------------------------------------+
| ``tx_maispace_seo_jsonld_image``           | int (FAL)       | JSON-LD image reference                      |
+--------------------------------------------+-----------------+----------------------------------------------+
| ``tx_maispace_seo_jsonld_author``          | varchar(255)    | JSON-LD author name                          |
+--------------------------------------------+-----------------+----------------------------------------------+
| ``tx_maispace_seo_jsonld_date_published``  | int (timestamp) | JSON-LD datePublished                        |
+--------------------------------------------+-----------------+----------------------------------------------+
| ``tx_maispace_seo_jsonld_date_modified``   | int (timestamp) | JSON-LD dateModified                         |
+--------------------------------------------+-----------------+----------------------------------------------+
| ``tx_maispace_seo_jsonld_custom``          | text            | Raw JSON-LD block (merged into schema)       |
+--------------------------------------------+-----------------+----------------------------------------------+
| ``tx_maispace_seo_og_title``              | varchar(255)    | ``og:title`` override                        |
+--------------------------------------------+-----------------+----------------------------------------------+
| ``tx_maispace_seo_og_description``        | text            | ``og:description`` override                  |
+--------------------------------------------+-----------------+----------------------------------------------+
| ``tx_maispace_seo_og_image``              | int (FAL)       | ``og:image`` reference                       |
+--------------------------------------------+-----------------+----------------------------------------------+
| ``tx_maispace_seo_og_type``               | varchar(50)     | ``og:type`` (website, article, …)            |
+--------------------------------------------+-----------------+----------------------------------------------+
| ``tx_maispace_seo_twitter_card``          | varchar(50)     | ``twitter:card`` type                        |
+--------------------------------------------+-----------------+----------------------------------------------+
| ``tx_maispace_seo_twitter_image``         | int (FAL)       | ``twitter:image`` reference                  |
+--------------------------------------------+-----------------+----------------------------------------------+
| ``tx_maispace_seo_canonical_url``         | varchar(2048)   | Canonical URL override                       |
+--------------------------------------------+-----------------+----------------------------------------------+
| ``tx_maispace_seo_robots_noindex``        | smallint        | Robots ``noindex`` flag                      |
+--------------------------------------------+-----------------+----------------------------------------------+
| ``tx_maispace_seo_robots_nofollow``       | smallint        | Robots ``nofollow`` flag                     |
+--------------------------------------------+-----------------+----------------------------------------------+
| ``tx_maispace_seo_robots_noarchive``      | smallint        | Robots ``noarchive`` flag                    |
+--------------------------------------------+-----------------+----------------------------------------------+

No further configuration is required after running the database update.
