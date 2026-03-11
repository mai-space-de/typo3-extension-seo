<?php

declare(strict_types=1);

namespace Maispace\MaispacesSeo\Service;

use Maispace\MaispacesSeo\Event\BeforeOpenGraphRenderedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class OpenGraphService
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher
    ) {}

    /**
     * Build an array of Open Graph and Twitter meta properties.
     *
     * @param array<string, mixed> $pageRecord
     * @param array<string, mixed> $settings
     * @return list<array{property: string, content: string}>
     */
    public function buildProperties(
        array $pageRecord,
        array $settings,
        string $ogImageUrl = '',
        string $twitterImageUrl = ''
    ): array {
        $properties = [];

        // og:type
        $ogType = (string)($pageRecord['tx_maispace_seo_og_type'] ?? '');
        if ($ogType === '') {
            $ogType = 'website';
        }
        $properties[] = ['property' => 'og:type', 'content' => $ogType];

        // og:title — override or fall back to page title
        $ogTitle = (string)($pageRecord['tx_maispace_seo_og_title'] ?? '');
        if ($ogTitle === '') {
            $ogTitle = (string)($pageRecord['title'] ?? '');
        }
        if ($ogTitle !== '') {
            $properties[] = ['property' => 'og:title', 'content' => $ogTitle];
        }

        // og:description — override or fall back to abstract
        $ogDescription = (string)($pageRecord['tx_maispace_seo_og_description'] ?? '');
        if ($ogDescription === '') {
            $ogDescription = (string)($pageRecord['abstract'] ?? '');
        }
        if ($ogDescription !== '') {
            $properties[] = ['property' => 'og:description', 'content' => $ogDescription];
        }

        // og:site_name
        $siteName = (string)($settings['openGraph.']['siteName'] ?? '');
        if ($siteName !== '') {
            $properties[] = ['property' => 'og:site_name', 'content' => $siteName];
        }

        // og:url — empty by default; ViewHelper provides from context
        $properties[] = ['property' => 'og:url', 'content' => ''];

        // og:image
        if ($ogImageUrl === '') {
            $ogImageUrl = (string)($settings['openGraph.']['defaultImage'] ?? '');
        }
        if ($ogImageUrl !== '') {
            $properties[] = ['property' => 'og:image', 'content' => $ogImageUrl];
        }

        // Twitter properties
        $twitterEnabled = (string)($settings['openGraph.']['twitter'] ?? '1');
        if ($twitterEnabled === '1') {
            $twitterCard = (string)($pageRecord['tx_maispace_seo_twitter_card'] ?? '');
            if ($twitterCard === '') {
                $twitterCard = 'summary';
            }
            $properties[] = ['property' => 'twitter:card', 'content' => $twitterCard];

            $twitterSite = (string)($settings['openGraph.']['twitterSite'] ?? '');
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
}
