<?php

namespace App\Support;

class RichContent
{
    public static function toText(?string $html): string
    {
        if (blank($html)) {
            return '';
        }

        $withSpacing = preg_replace('/<\/(p|li|h[1-6]|blockquote|tr)>/i', '$0 ', $html) ?? $html;

        return trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($withSpacing), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
    }
}
