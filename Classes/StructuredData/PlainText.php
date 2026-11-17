<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\StructuredData;

/**
 * Reduces editorial HTML to plain text for schema.org text properties such as "description".
 */
final class PlainText
{
    public static function from(mixed $value): string
    {
        $html = is_scalar($value) ? (string) $value : '';
        if ($html === '') {
            return '';
        }

        $spaced = (string) preg_replace('/<\/(p|li|h[1-6]|div)>|<br\s*\/?>/i', ' $0', $html);
        $text = html_entity_decode(strip_tags($spaced), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/[\s\x{00A0}]+/u', ' ', $text));
    }

    /**
     * Returns the first of the given values that is not empty as plain text.
     */
    public static function firstOf(mixed ...$values): string
    {
        foreach ($values as $value) {
            $text = self::from($value);
            if ($text !== '') {
                return $text;
            }
        }

        return '';
    }
}
