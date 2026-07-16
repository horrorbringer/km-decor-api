<?php

namespace App\Support;

use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

final class AdminSeo
{
    public static function syncTitle(Get $get, Set $set, ?string $old, ?string $title, string $type): void
    {
        $current = $get('meta_title');

        if (blank($current) || $current === self::title($old)) {
            $set('meta_title', self::title($title));
        }

        self::syncStructuredData($get, $set, $type, $title, $get('meta_description'));
    }

    public static function syncDescription(Get $get, Set $set, ?string $old, ?string $description, string $type): void
    {
        $current = $get('meta_description');

        if (blank($current) || $current === self::description($old)) {
            $set('meta_description', self::description($description));
        }

        self::syncStructuredData($get, $set, $type, $get('meta_title'), $description);
    }

    public static function title(?string $value): string
    {
        return Str::limit(RichContent::toText($value), 60, '');
    }

    public static function description(?string $value): string
    {
        return Str::limit(RichContent::toText($value), 160, '');
    }

    public static function structuredData(?array $current, string $type, ?string $title, ?string $description): array
    {
        return array_filter([
            ...($current ?? []),
            '@context' => 'https://schema.org',
            '@type' => $type,
            'name' => self::title($title),
            'description' => self::description($description),
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }

    private static function syncStructuredData(Get $get, Set $set, string $type, ?string $title, ?string $description): void
    {
        $set('structured_data', self::structuredData(
            is_array($get('structured_data')) ? $get('structured_data') : [],
            $type,
            $title,
            $description,
        ));
    }
}
