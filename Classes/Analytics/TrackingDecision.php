<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Analytics;

enum TrackingDecision: string
{
    case Allow = 'allow';
    case Disabled = 'disabled';
    case BackendUser = 'backend-user';
    case Method = 'method';
    case Bot = 'bot';
    case Preview = 'preview';
    case Origin = 'origin';
}
