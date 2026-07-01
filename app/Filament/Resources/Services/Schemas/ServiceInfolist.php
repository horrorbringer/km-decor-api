<?php

namespace App\Filament\Resources\Services\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ServiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Service Information')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('name_kh')
                            ->placeholder('-'),
                        TextEntry::make('slug'),
                        TextEntry::make('category.name')
                            ->label('Category')
                            ->placeholder('-'),
                        TextEntry::make('inquiry_type')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'quote' => 'info',
                                'contact' => 'warning',
                                'booking' => 'success',
                                default => 'gray',
                            }),
                        TextEntry::make('sort_order')
                            ->numeric(),
                        IconEntry::make('is_active')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),
                        IconEntry::make('is_featured')
                            ->boolean(),
                    ]),

                Section::make('Description')
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
                        TextEntry::make('faqs')
                            ->placeholder('-')
                            ->html()
                            ->columnSpanFull(),
                    ]),

                Section::make('Media')
                    ->columns(2)
                    ->schema([
                        ImageEntry::make('image_url')
                            ->placeholder('-'),
                        TextEntry::make('portfolio_images')
                            ->placeholder('-'),
                    ]),

                Section::make('Metadata')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id')
                            ->label('ID'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ]),
            ]);
    }
}
