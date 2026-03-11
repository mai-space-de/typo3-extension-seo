<?php

declare(strict_types = 1);

namespace Maispace\MaispacesSeo\Service;

use Maispace\MaispacesSeo\Event\BeforeJsonLdRenderedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class JsonLdService
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * Build the JSON-LD schema array from a page record and TypoScript settings.
     *
     * @param array<string, mixed> $pageRecord
     * @param array<string, mixed> $settings
     *
     * @return array<string, mixed>
     */
    public function buildSchema(array $pageRecord, array $settings): array
    {
        $type = self::str($pageRecord['tx_maispace_seo_jsonld_type'] ?? null);
        if ($type === '') {
            $jsonLdSettings = is_array($settings['jsonLd.'] ?? null) ? $settings['jsonLd.'] : [];
            $type = self::str($jsonLdSettings['defaultType'] ?? null) ?: 'WebPage';
        }

        /** @var array<string, mixed> $schema */
        $schema = [
            '@context' => 'https://schema.org',
            '@type'    => $type,
        ];

        // Name: use override or fall back to page title
        $name = self::str($pageRecord['tx_maispace_seo_jsonld_name'] ?? null);
        if ($name === '') {
            $name = self::str($pageRecord['title'] ?? null);
        }
        if ($name !== '') {
            $schema['name'] = $name;
        }

        // Description: use override or fall back to abstract
        $description = self::str($pageRecord['tx_maispace_seo_jsonld_description'] ?? null);
        if ($description === '') {
            $description = self::str($pageRecord['abstract'] ?? null);
        }
        if ($description !== '') {
            $schema['description'] = $description;
        }

        // URL
        $url = self::str($pageRecord['canonical_link'] ?? null);
        if ($url !== '') {
            $schema['url'] = $url;
        }

        // Date published
        $datePublished = self::int_($pageRecord['tx_maispace_seo_jsonld_date_published'] ?? null);
        if ($datePublished > 0) {
            $schema['datePublished'] = date('c', $datePublished);
        }

        // Date modified
        $dateModified = self::int_($pageRecord['tx_maispace_seo_jsonld_date_modified'] ?? null);
        if ($dateModified > 0) {
            $schema['dateModified'] = date('c', $dateModified);
        }

        // Author
        $author = self::str($pageRecord['tx_maispace_seo_jsonld_author'] ?? null);
        if ($author !== '') {
            $schema['author'] = ['@type' => 'Person', 'name' => $author];
        }

        // Publisher from TypoScript settings
        $jsonLdSettings = is_array($settings['jsonLd.'] ?? null) ? $settings['jsonLd.'] : [];
        $organizationName = self::str($jsonLdSettings['organizationName'] ?? null);
        if ($organizationName !== '') {
            $publisher = ['@type' => 'Organization', 'name' => $organizationName];

            $organizationUrl = self::str($jsonLdSettings['organizationUrl'] ?? null);
            if ($organizationUrl !== '') {
                $publisher['url'] = $organizationUrl;
            }

            $organizationLogo = self::str($jsonLdSettings['organizationLogo'] ?? null);
            if ($organizationLogo !== '') {
                $publisher['logo'] = $organizationLogo;
            }

            $schema['publisher'] = $publisher;
        }

        // Merge custom JSON-LD if set and valid
        $customJson = self::str($pageRecord['tx_maispace_seo_jsonld_custom'] ?? null);
        if ($customJson !== '') {
            $decoded = json_decode($customJson, true);
            if (is_array($decoded)) {
                /** @var array<string, mixed> $schema */
                $schema = array_merge($schema, $decoded);
            }
        }

        /** @var BeforeJsonLdRenderedEvent $event */
        $event = $this->eventDispatcher->dispatch(new BeforeJsonLdRenderedEvent($schema));

        if (!$event->isEnabled()) {
            return [];
        }

        return $event->getSchema();
    }

    /**
     * Render the JSON-LD schema array as an inline <script> tag.
     *
     * @param array<string, mixed> $schema
     */
    public function renderScript(array $schema): string
    {
        if ($schema === []) {
            return '';
        }

        return '<script type="application/ld+json">'
            . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            . '</script>';
    }

    private static function str(mixed $value): string
    {
        return is_scalar($value) ? (string)$value : '';
    }

    private static function int_(mixed $value): int
    {
        return is_scalar($value) ? (int)$value : 0;
    }
}
