<?php

declare(strict_types = 1);

namespace Maispace\MaispacesSeo\Service;

use Maispace\MaispacesSeo\Event\BeforeCanonicalRenderedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class CanonicalService
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * Build the canonical URL from a page record and TypoScript settings.
     *
     * Priority:
     *   1. Custom override field `tx_maispace_seo_canonical_url`
     *   2. TYPO3 core `canonical_link` field
     *
     * Returns an empty string when the feature is disabled via TypoScript or
     * when a listener calls {@see BeforeCanonicalRenderedEvent::disable()}.
     *
     * @param array<string, mixed> $pageRecord
     * @param array<string, mixed> $settings
     */
    public function buildCanonicalUrl(array $pageRecord, array $settings): string
    {
        $canonicalSettings = is_array($settings['canonical.'] ?? null) ? $settings['canonical.'] : [];
        $enabled = (string)($canonicalSettings['enable'] ?? '1');
        if ($enabled !== '1') {
            return '';
        }

        // Custom override field takes precedence
        $url = self::str($pageRecord['tx_maispace_seo_canonical_url'] ?? null);

        // Fall back to TYPO3 core canonical_link
        if ($url === '') {
            $url = self::str($pageRecord['canonical_link'] ?? null);
        }

        /** @var BeforeCanonicalRenderedEvent $event */
        $event = $this->eventDispatcher->dispatch(new BeforeCanonicalRenderedEvent($url, $pageRecord));

        if (!$event->isEnabled()) {
            return '';
        }

        return $event->getCanonicalUrl();
    }

    /**
     * Render the canonical URL as a <link rel="canonical"> tag.
     */
    public function renderTag(string $canonicalUrl): string
    {
        if ($canonicalUrl === '') {
            return '';
        }

        return '<link rel="canonical" href="' . htmlspecialchars($canonicalUrl, ENT_QUOTES | ENT_HTML5) . '">';
    }

    private static function str(mixed $value): string
    {
        return is_scalar($value) ? (string)$value : '';
    }
}
