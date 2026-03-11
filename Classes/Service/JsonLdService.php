<?php

declare(strict_types=1);

namespace Maispace\MaispacesSeo\Service;

use Maispace\MaispacesSeo\Event\BeforeJsonLdRenderedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class JsonLdService
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher
    ) {}

    /**
     * Build the JSON-LD schema array from a page record and TypoScript settings.
     *
     * @param array<string, mixed> $pageRecord
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public function buildSchema(array $pageRecord, array $settings): array
    {
        $type = (string)($pageRecord['tx_maispace_seo_jsonld_type'] ?? '');
        if ($type === '') {
            $type = (string)($settings['jsonLd.']['defaultType'] ?? 'WebPage');
        }
        if ($type === '') {
            $type = 'WebPage';
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type'    => $type,
        ];

        // Name: use override or fall back to page title
        $name = (string)($pageRecord['tx_maispace_seo_jsonld_name'] ?? '');
        if ($name === '') {
            $name = (string)($pageRecord['title'] ?? '');
        }
        if ($name !== '') {
            $schema['name'] = $name;
        }

        // Description: use override or fall back to abstract
        $description = (string)($pageRecord['tx_maispace_seo_jsonld_description'] ?? '');
        if ($description === '') {
            $description = (string)($pageRecord['abstract'] ?? '');
        }
        if ($description !== '') {
            $schema['description'] = $description;
        }

        // URL
        $url = (string)($pageRecord['canonical_link'] ?? '');
        if ($url !== '') {
            $schema['url'] = $url;
        }

        // Date published
        $datePublished = (int)($pageRecord['tx_maispace_seo_jsonld_date_published'] ?? 0);
        if ($datePublished > 0) {
            $schema['datePublished'] = date('c', $datePublished);
        }

        // Date modified
        $dateModified = (int)($pageRecord['tx_maispace_seo_jsonld_date_modified'] ?? 0);
        if ($dateModified > 0) {
            $schema['dateModified'] = date('c', $dateModified);
        }

        // Author
        $author = (string)($pageRecord['tx_maispace_seo_jsonld_author'] ?? '');
        if ($author !== '') {
            $schema['author'] = ['@type' => 'Person', 'name' => $author];
        }

        // Publisher from TypoScript settings
        $organizationName = (string)($settings['jsonLd.']['organizationName'] ?? '');
        if ($organizationName !== '') {
            $publisher = ['@type' => 'Organization', 'name' => $organizationName];

            $organizationUrl = (string)($settings['jsonLd.']['organizationUrl'] ?? '');
            if ($organizationUrl !== '') {
                $publisher['url'] = $organizationUrl;
            }

            $organizationLogo = (string)($settings['jsonLd.']['organizationLogo'] ?? '');
            if ($organizationLogo !== '') {
                $publisher['logo'] = $organizationLogo;
            }

            $schema['publisher'] = $publisher;
        }

        // Merge custom JSON-LD if set and valid
        $customJson = (string)($pageRecord['tx_maispace_seo_jsonld_custom'] ?? '');
        if ($customJson !== '') {
            $decoded = json_decode($customJson, true);
            if (is_array($decoded)) {
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
}
