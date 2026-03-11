<?php

declare(strict_types = 1);

namespace Maispace\MaispacesSeo\Service;

use Maispace\MaispacesSeo\Event\BeforeOpenGraphRenderedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class OpenGraphService
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * Build an array of Open Graph and Twitter meta properties.
     *
     * @param array<string, mixed> $pageRecord
     * @param array<string, mixed> $settings
     *
     * @return list<array{property: string, content: string}>
     */
    public function buildProperties(
        array $pageRecord,
        array $settings,
        string $ogImageUrl = '',
        string $twitterImageUrl = '',
        string $ogUrl = ''
    ): array {
        $properties = [];

        $ogSettings = is_array($settings['openGraph.'] ?? null) ? $settings['openGraph.'] : [];

        // og:type
        $ogType = self::str($pageRecord['tx_maispace_seo_og_type'] ?? null);
        if ($ogType === '') {
            $ogType = 'website';
        }
        $properties[] = ['property' => 'og:type', 'content' => $ogType];

        // og:title — override or fall back to page title
        $ogTitle = self::str($pageRecord['tx_maispace_seo_og_title'] ?? null);
        if ($ogTitle === '') {
            $ogTitle = self::str($pageRecord['title'] ?? null);
        }
        if ($ogTitle !== '') {
            $properties[] = ['property' => 'og:title', 'content' => $ogTitle];
        }

        // og:description — override or fall back to abstract
        $ogDescription = self::str($pageRecord['tx_maispace_seo_og_description'] ?? null);
        if ($ogDescription === '') {
            $ogDescription = self::str($pageRecord['abstract'] ?? null);
        }
        if ($ogDescription !== '') {
            $properties[] = ['property' => 'og:description', 'content' => $ogDescription];
        }

        // og:site_name
        $siteName = self::str($ogSettings['siteName'] ?? null);
        if ($siteName !== '') {
            $properties[] = ['property' => 'og:site_name', 'content' => $siteName];
        }

        // og:url — use canonical URL when provided
        if ($ogUrl !== '') {
            $properties[] = ['property' => 'og:url', 'content' => $ogUrl];
        }

        // og:image
        if ($ogImageUrl === '') {
            $ogImageUrl = self::str($ogSettings['defaultImage'] ?? null);
        }
        if ($ogImageUrl !== '') {
            $properties[] = ['property' => 'og:image', 'content' => $ogImageUrl];
        }

        // Twitter properties
        $rawTwitterEnabled = $ogSettings['twitter'] ?? null;
        $twitterEnabled = is_scalar($rawTwitterEnabled) ? (string)$rawTwitterEnabled : '1';
        if ($twitterEnabled === '1') {
            $twitterCard = self::str($pageRecord['tx_maispace_seo_twitter_card'] ?? null);
            if ($twitterCard === '') {
                $twitterCard = 'summary';
            }
            $properties[] = ['property' => 'twitter:card', 'content' => $twitterCard];

            $twitterSite = self::str($ogSettings['twitterSite'] ?? null);
            if ($twitterSite !== '') {
                $properties[] = ['property' => 'twitter:site', 'content' => $twitterSite];
            }

            if ($ogTitle !== '') {
                $properties[] = ['property' => 'twitter:title', 'content' => $ogTitle];
            }

            if ($ogDescription !== '') {
                $properties[] = ['property' => 'twitter:description', 'content' => $ogDescription];
            }

            if ($twitterImageUrl !== '') {
                $properties[] = ['property' => 'twitter:image', 'content' => $twitterImageUrl];
            } elseif ($ogImageUrl !== '') {
                $properties[] = ['property' => 'twitter:image', 'content' => $ogImageUrl];
            }
        }

        /** @var BeforeOpenGraphRenderedEvent $event */
        $event = $this->eventDispatcher->dispatch(new BeforeOpenGraphRenderedEvent($properties));

        if (!$event->isEnabled()) {
            return [];
        }

        return $event->getProperties();
    }

    private static function str(mixed $value): string
    {
        return is_scalar($value) ? (string)$value : '';
    }
}
