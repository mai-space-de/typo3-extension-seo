<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Analytics;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Site\Entity\Site;

class HitRecorder
{
    public function __construct(
        private readonly AnalyticsStore $store,
        private readonly VisitorHasher $hasher,
        private readonly ChannelClassifier $channels,
        private readonly ClientClassifier $clients,
    ) {}

    public function record(ServerRequestInterface $request, HitDraft $draft): void
    {
        if ($draft->duration > 0) {
            $this->store->updateDuration($draft->hitToken, $draft->duration);

            return;
        }

        if ($this->store->tokenExists($draft->hitToken) || !$this->store->pageExists($draft->pageId)) {
            return;
        }

        $site = $request->getAttribute('site');
        $siteId = $site instanceof Site ? $site->getIdentifier() : '';
        $normalized = $request->getAttribute('normalizedParams');
        $ip = $normalized instanceof NormalizedParams ? $normalized->getRemoteAddress() : '';
        $userAgent = $request->getHeaderLine('User-Agent');
        $ownHost = $request->getUri()->getHost();

        $this->store->insert([
            'pid' => $draft->pageId,
            'tstamp' => time(),
            'site' => substr($siteId, 0, 64),
            'language_id' => $draft->languageId,
            'locale' => $draft->locale,
            'path' => $draft->path,
            'title' => $draft->title,
            'referrer_host' => $this->channels->host($draft->referrer),
            'channel' => $this->channels->classify($draft->referrer, $ownHost),
            'device' => $this->clients->device($draft->screenWidth, $userAgent),
            'browser' => $this->clients->browser($userAgent),
            'visitor_hash' => $this->hasher->hash($ip, $userAgent, gmdate('Y-m-d'), $this->salt()),
            'hit_token' => $draft->hitToken,
            'duration' => 0,
        ]);
    }

    private function salt(): string
    {
        return (string) ($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] ?? 'mai-seo');
    }
}
