<?php

declare(strict_types = 1);

namespace Maispace\MaiSeo\Service;

use Maispace\MaiSeo\Event\BeforeMetaDescriptionRenderedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class MetaDescriptionService
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * Build the meta description string from a page record and TypoScript settings.
     *
     * Priority:
     *   1. Custom override field `tx_maiseo_meta_description`
     *   2. TYPO3 core `description` field
     *   3. TYPO3 `abstract` field
     *
     * Returns an empty string when the feature is disabled via TypoScript or
     * when a listener calls {@see BeforeMetaDescriptionRenderedEvent::disable()}.
     *
     * @param array<string, mixed> $pageRecord
     * @param array<string, mixed> $settings
     */
    public function buildDescription(array $pageRecord, array $settings): string
    {
        $metaSettings = is_array($settings['metaDescription.'] ?? null) ? $settings['metaDescription.'] : [];
        $rawEnabled = $metaSettings['enable'] ?? '1';
        $enabled = is_scalar($rawEnabled) ? (string)$rawEnabled : '1';
        if ($enabled !== '1') {
            return '';
        }

        // Custom override field takes precedence
        $description = self::str($pageRecord['tx_maiseo_meta_description'] ?? null);

        // Fall back to TYPO3 core description field
        if ($description === '') {
            $description = self::str($pageRecord['description'] ?? null);
        }

        // Fall back to abstract
        if ($description === '') {
            $description = self::str($pageRecord['abstract'] ?? null);
        }

        /** @var BeforeMetaDescriptionRenderedEvent $event */
        $event = $this->eventDispatcher->dispatch(new BeforeMetaDescriptionRenderedEvent($description, $pageRecord));

        if (!$event->isEnabled()) {
            return '';
        }

        return $event->getDescription();
    }

    /**
     * Render the description as a <meta name="description"> tag.
     */
    public function renderTag(string $description): string
    {
        if ($description === '') {
            return '';
        }

        return '<meta name="description" content="' . htmlspecialchars($description, ENT_QUOTES | ENT_HTML5) . '">';
    }

    private static function str(mixed $value): string
    {
        return is_scalar($value) ? (string)$value : '';
    }
}
