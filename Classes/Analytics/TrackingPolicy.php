<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Analytics;

/**
 * Entscheidet, ob ein Frontend-Aufruf gespeichert wird.
 * Angemeldete Backend-Benutzer werden immer verworfen, auch wenn die Seite aus dem Cache kommt.
 */
final class TrackingPolicy
{
    public function decide(TrackingContext $context): TrackingDecision
    {
        if (!$context->enabled) {
            return TrackingDecision::Disabled;
        }

        if (strtoupper($context->method) !== 'POST') {
            return TrackingDecision::Method;
        }

        if ($context->backendUserLoggedIn) {
            return TrackingDecision::BackendUser;
        }

        if (!$context->sameOrigin) {
            return TrackingDecision::Origin;
        }

        if ($this->isPreview($context->requestPath) || $this->isBot($context->userAgent)) {
            return $this->isPreview($context->requestPath) ? TrackingDecision::Preview : TrackingDecision::Bot;
        }

        return TrackingDecision::Allow;
    }

    private function isPreview(string $path): bool
    {
        return str_contains($path, 'ADMCMD_');
    }

    private function isBot(string $userAgent): bool
    {
        $agent = strtolower(trim($userAgent));
        if ($agent === '') {
            return true;
        }

        return preg_match(
            '/bot|crawl|spider|slurp|headless|wget|curl|python-requests|go-http-client|facebookexternalhit|ahrefs|semrush|petalbot|bytespider|gptbot|claudebot|amazonbot/',
            $agent,
        ) === 1;
    }
}
