<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Analytics;

use TYPO3\CMS\Core\Context\Context;

final class ContextBackendUserDetector implements BackendUserDetector
{
    public function __construct(
        private readonly Context $context,
    ) {}

    public function isLoggedIn(): bool
    {
        return (bool) $this->context->getPropertyFromAspect('backend.user', 'isLoggedIn', false);
    }
}
