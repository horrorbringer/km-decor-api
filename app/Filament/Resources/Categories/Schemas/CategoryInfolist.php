<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Category Information')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('name_kh')
                            ->placeholder('-'),
                        TextEntry::make('slug'),
                        TextEntry::make('parent.name')
                            ->label('Parent')
                            ->placeholder('-'),
                        TextEntry::make('type')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'product' => 'info',
                                'service' => 'warning',
                                default => 'gray',
                            }),
                        TextEntry::make('icon')
                            ->placeholder('-'),
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
                        TextEntry::make('description')
                            ->placeholder('-')
                            ->html()
                            ->columnSpanFull(),
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
