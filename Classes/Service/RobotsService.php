<?php

declare(strict_types = 1);

namespace Maispace\MaispacesSeo\Service;

use Maispace\MaispacesSeo\Event\BeforeRobotsRenderedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class RobotsService
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * Build the robots directives string from a page record and TypoScript settings.
     *
     * Generates a comma-separated list of robot instructions:
     *   - index/noindex  — controlled by `tx_maispace_seo_robots_noindex`
     *   - follow/nofollow — controlled by `tx_maispace_seo_robots_nofollow`
     *   - noarchive      — added when `tx_maispace_seo_robots_noarchive` is set
     *
     * Returns an empty string when the feature is disabled via TypoScript or
     * when a listener calls {@see BeforeRobotsRenderedEvent::disable()}.
     *
     * @param array<string, mixed> $pageRecord
     * @param array<string, mixed> $settings
     */
    public function buildDirectives(array $pageRecord, array $settings): string
    {
        $robotsSettings = is_array($settings['robots.'] ?? null) ? $settings['robots.'] : [];
        $rawEnabled = $robotsSettings['enable'] ?? '1';
        $enabled = is_scalar($rawEnabled) ? (string)$rawEnabled : '1';
        if ($enabled !== '1') {
            return '';
        }

        $parts = [];

        $noIndex = self::int_($pageRecord['tx_maispace_seo_robots_noindex'] ?? null) !== 0;
        $parts[] = $noIndex ? 'noindex' : 'index';

        $noFollow = self::int_($pageRecord['tx_maispace_seo_robots_nofollow'] ?? null) !== 0;
        $parts[] = $noFollow ? 'nofollow' : 'follow';

        $noArchive = self::int_($pageRecord['tx_maispace_seo_robots_noarchive'] ?? null) !== 0;
        if ($noArchive) {
            $parts[] = 'noarchive';
        }

        $directives = implode(', ', $parts);

        /** @var BeforeRobotsRenderedEvent $event */
        $event = $this->eventDispatcher->dispatch(new BeforeRobotsRenderedEvent($directives, $pageRecord));

        if (!$event->isEnabled()) {
            return '';
        }

        return $event->getDirectives();
    }

    /**
     * Render the robots directives as a <meta name="robots"> tag.
     */
    public function renderTag(string $directives): string
    {
        if ($directives === '') {
            return '';
        }

        return '<meta name="robots" content="' . htmlspecialchars($directives, ENT_QUOTES | ENT_HTML5) . '">';
    }

    private static function int_(mixed $value): int
    {
        return is_scalar($value) ? (int)$value : 0;
    }
}
