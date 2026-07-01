<?php

namespace App\Filament\Resources\Brands\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class BrandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required(),
                        TextInput::make('name_kh')
                            ->default(null),
                        TextInput::make('slug')
                            ->required(),
                        TextInput::make('country_of_origin')
                            ->default(null),
                        TextInput::make('website_url')
                            ->url()
                            ->default(null),
                        TextInput::make('sort_order')
                            ->required()
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_active')
                            ->required(),
                        Toggle::make('is_featured')
                            ->required(),
                    ]),

                Section::make('Logo & Description')
                    ->schema([
                        FileUpload::make('logo')
                            ->label('Brand Logo')
                            ->image()
                            ->imageEditor()
                            ->directory('brands')
                            ->visibility('public')
                            ->maxFiles(1),
                        RichEditor::make('description')
                            ->default(null)
                            ->columnSpanFull(),
                        RichEditor::make('description_kh')
                            ->default(null)
                            ->columnSpanFull(),
                    ]),

                Section::make('SEO & Metadata')
                    ->schema([
                        Tabs::make('SEO')
                            ->tabs([
                                Tab::make('Meta')
                                    ->schema([
                                        TextInput::make('meta_title')
                                            ->label('Meta Title')
                                            ->maxLength(60)
                                            ->helperText('Max 60 characters for Google'),
                                        Textarea::make('meta_description')
                                            ->label('Meta Description')
                                            ->rows(3)
                                            ->maxLength(160)
                                            ->helperText('Max 160 characters for Google'),
                                        FileUpload::make('og_image')
                                            ->label('Open Graph Image')
                                            ->image()
                                            ->directory('brands/og')
                                            ->visibility('public')
                                            ->maxFiles(1)
                                            ->columnSpanFull(),
                                    ]),
                                Tab::make('Structured Data')
                                    ->schema([
                                        KeyValue::make('structured_data')
                                            ->label('JSON-LD Structured Data')
                                            ->keyLabel('Key')
                                            ->valueLabel('Value')
                                            ->addActionLabel('Add Property')
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
