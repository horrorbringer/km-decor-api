<?php

namespace App\Filament\Resources\Portfolios\Schemas;

use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProjectInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Project Details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('title'),
                        TextEntry::make('title_kh'),
                        TextEntry::make('slug'),
                        TextEntry::make('setting'),
                        TextEntry::make('focus'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'published' => 'success',
                                'draft' => 'gray',
                                default => 'gray',
                            }),
                        TextEntry::make('sort_order'),
                        TextEntry::make('published_at')
                            ->dateTime(),
                    ]),
                Section::make('Content')
                    ->schema([
                        TextEntry::make('goal')
                            ->placeholder('-')
                            ->html()
                            ->columnSpanFull(),
                        TextEntry::make('overview')
                            ->placeholder('-')
                            ->html()
                            ->columnSpanFull(),
                        TextEntry::make('challenge')
                            ->placeholder('-')
                            ->html()
                            ->columnSpanFull(),
                        TextEntry::make('response')
                            ->placeholder('-')
                            ->html()
                            ->columnSpanFull(),
                        TextEntry::make('scope')
                            ->formatStateUsing(fn ($state) => is_array($state) ? implode("\n", $state) : $state),
                        TextEntry::make('outcomes')
                            ->formatStateUsing(fn ($state) => is_array($state) ? implode("\n", $state) : $state),
                    ]),
                Section::make('Gallery')
                    ->schema([
                        SpatieMediaLibraryImageEntry::make('gallery')
                            ->label('Images')
                            ->collection('gallery')
                            ->height(200)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
