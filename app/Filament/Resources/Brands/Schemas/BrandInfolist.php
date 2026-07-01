<?php

namespace App\Filament\Resources\Brands\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BrandInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Brand Information')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('name_kh')
                            ->placeholder('-'),
                        TextEntry::make('slug'),
                        TextEntry::make('country_of_origin')
                            ->placeholder('-'),
                        TextEntry::make('website_url')
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
                        TextEntry::make('description_kh')
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
