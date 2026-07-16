<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('name_kh')
                            ->placeholder('-'),
                        TextEntry::make('slug'),
                        TextEntry::make('sku')
                            ->label('SKU'),
                        TextEntry::make('category.name')
                            ->label('Category'),
                        TextEntry::make('brand.name')
                            ->label('Brand')
                            ->placeholder('-'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'published' => 'success',
                                'draft' => 'gray',
                                'archived' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('published_at')
                            ->dateTime()
                            ->placeholder('-'),
                    ]),

                Section::make('Description')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('short_description'),
                        TextEntry::make('short_description_kh')
                            ->placeholder('-'),
                        TextEntry::make('description')
                            ->placeholder('-')
                            ->html()
                            ->columnSpanFull(),
                        TextEntry::make('description_kh')
                            ->placeholder('-')
                            ->html()
                            ->columnSpanFull(),
                        TextEntry::make('customer_goal')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('features')
                            ->placeholder('-')
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->columnSpanFull(),
                        TextEntry::make('applications')
                            ->placeholder('-')
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->columnSpanFull(),
                        TextEntry::make('material_notes')
                            ->placeholder('-')
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->columnSpanFull(),
                        TextEntry::make('lead_time')
                            ->placeholder('-'),
                        TextEntry::make('delivery_note')
                            ->placeholder('-'),
                        TextEntry::make('compatible_product_slugs')
                            ->placeholder('-')
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->columnSpanFull(),
                        TextEntry::make('specifications')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('tags')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),

                Section::make('Pricing & Inventory')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('price')
                            ->money('USD'),
                        TextEntry::make('compare_price')
                            ->money('USD')
                            ->placeholder('-'),
                        TextEntry::make('currency'),
                        TextEntry::make('unit'),
                        TextEntry::make('stock_qty')
                            ->numeric()
                            ->color(fn ($state): ?string => match (true) {
                                $state === 0 => 'danger',
                                $state < 10 => 'warning',
                                default => 'success',
                            }),
                        TextEntry::make('min_order_qty')
                            ->numeric(),
                        IconEntry::make('allow_backorder')
                            ->boolean(),
                        IconEntry::make('requires_installation')
                            ->boolean(),
                        TextEntry::make('warranty_months')
                            ->numeric()
                            ->suffix(' months')
                            ->placeholder('-'),
                    ]),

                Section::make('Ratings & Flags')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('avg_rating')
                            ->numeric(),
                        TextEntry::make('review_count')
                            ->numeric(),
                        IconEntry::make('is_featured')
                            ->boolean(),
                        IconEntry::make('is_new')
                            ->boolean(),
                        IconEntry::make('is_best_seller')
                            ->boolean(),
                        TextEntry::make('sort_order')
                            ->numeric(),
                    ]),

                Section::make('Timestamps')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id')
                            ->label('ID'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                        TextEntry::make('deleted_at')
                            ->dateTime()
                            ->visible(fn (Product $record): bool => $record->trashed()),
                    ]),
            ]);
    }
}
