<?php

declare(strict_types = 1);

namespace Maispace\MaispacesSeo\Service;

use Maispace\MaispacesSeo\Event\BeforeAiRobotsRenderedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class AiRobotsService
{
    /**
     * Default AI crawler bot names that honour per-bot meta tags.
     */
    private const DEFAULT_BOTS = 'GPTBot, OAI-SearchBot, ClaudeBot, Google-Extended, PerplexityBot, CCBot, Bytespider, Amazonbot';

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * Build the list of AI robots meta tags from a page record and TypoScript settings.
     *
     * When `tx_maispace_seo_ai_noindex` is set, a `noindex` meta tag is emitted
     * for every bot listed in `aiRobots.bots` (TypoScript), defaulting to a
     * curated list of well-known AI crawlers.
     *
     * Returns an empty array when the feature is disabled via TypoScript or
     * when a listener calls {@see BeforeAiRobotsRenderedEvent::disable()}.
     *
     * @param array<string, mixed> $pageRecord
     * @param array<string, mixed> $settings
     *
     * @return list<array{name: string, content: string}>
     */
    public function buildTags(array $pageRecord, array $settings): array
    {
        $aiSettings = is_array($settings['aiRobots.'] ?? null) ? $settings['aiRobots.'] : [];
        $rawEnabled = $aiSettings['enable'] ?? '1';
        $enabled = is_scalar($rawEnabled) ? (string)$rawEnabled : '1';
        if ($enabled !== '1') {
            return [];
        }

        $tags = [];

        $noIndex = self::int_($pageRecord['tx_maispace_seo_ai_noindex'] ?? null) !== 0;
        if ($noIndex) {
            $botsConfig = self::str($aiSettings['bots'] ?? null);
            if ($botsConfig === '') {
                $botsConfig = self::DEFAULT_BOTS;
            }
            $bots = array_values(array_filter(
                array_map('trim', explode(',', $botsConfig)),
                static fn (string $b): bool => $b !== ''
            ));
            foreach ($bots as $bot) {
                $tags[] = ['name' => $bot, 'content' => 'noindex'];
            }
        }

        /** @var BeforeAiRobotsRenderedEvent $event */
        $event = $this->eventDispatcher->dispatch(new BeforeAiRobotsRenderedEvent($tags, $pageRecord));

        if (!$event->isEnabled()) {
            return [];
        }

        return $event->getTags();
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
