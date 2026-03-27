<?php

declare(strict_types = 1);

namespace Maispace\MaiSeo\Service;

use Maispace\MaiSeo\Event\BeforeLlmsTxtRenderedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class LlmsTxtService
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * Build the llms.txt URL from TypoScript settings.
     *
     * The URL is read from `llmsTxt.url` (defaults to `/llms.txt`).
     *
     * Returns an empty string when the feature is disabled via TypoScript or
     * when a listener calls {@see BeforeLlmsTxtRenderedEvent::disable()}.
     *
     * @param array<string, mixed> $pageRecord
     * @param array<string, mixed> $settings
     */
    public function buildUrl(array $pageRecord, array $settings): string
    {
        $llmsSettings = is_array($settings['llmsTxt.'] ?? null) ? $settings['llmsTxt.'] : [];
        $rawEnabled = $llmsSettings['enable'] ?? '1';
        $enabled = is_scalar($rawEnabled) ? (string)$rawEnabled : '1';
        if ($enabled !== '1') {
            return '';
        }

        $url = self::str($llmsSettings['url'] ?? null);
        if ($url === '') {
            $url = '/llms.txt';
        }

        /** @var BeforeLlmsTxtRenderedEvent $event */
        $event = $this->eventDispatcher->dispatch(new BeforeLlmsTxtRenderedEvent($url, $pageRecord));

        if (!$event->isEnabled()) {
            return '';
        }

        return $event->getUrl();
    }

    /**
     * Render the llms.txt URL as a <link rel="llms-txt"> tag.
     */
    public function renderTag(string $url): string
    {
        if ($url === '') {
            return '';
        }

        return '<link rel="llms-txt" href="' . htmlspecialchars($url, ENT_QUOTES | ENT_HTML5) . '" type="text/plain">';
    }

    private static function str(mixed $value): string
    {
        return is_scalar($value) ? (string)$value : '';
    }
}
