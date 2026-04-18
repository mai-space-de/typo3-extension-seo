<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Utility;

final class JsonMerge
{
    public const REMOVE_SENTINEL = '__remove__';

    public static function deepMerge(array $base, array $overrides): array
    {
        $result = $base;

        foreach ($overrides as $key => $value) {
            if ($value === self::REMOVE_SENTINEL) {
                unset($result[$key]);
                continue;
            }

            if (isset($result[$key]) && is_array($result[$key]) && is_array($value)) {
                $result[$key] = self::deepMerge($result[$key], $value);
                continue;
            }

            $result[$key] = $value;
        }

        return self::removeSentinels($result);
    }

    private static function removeSentinels(array $data): array
    {
        $cleaned = [];
        foreach ($data as $key => $value) {
            if ($value === self::REMOVE_SENTINEL) {
                continue;
            }
            $cleaned[$key] = is_array($value) ? self::removeSentinels($value) : $value;
        }
        return $cleaned;
    }
}
