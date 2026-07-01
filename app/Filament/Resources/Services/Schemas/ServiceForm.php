<?php

namespace App\Filament\Resources\Services\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class ServiceForm
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
                        Select::make('category_id')
                            ->relationship('category', 'name'),
                        TextInput::make('inquiry_type')
                            ->required()
                            ->default('quote'),
                        TextInput::make('sort_order')
                            ->required()
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_active')
                            ->required(),
                        Toggle::make('is_featured')
                            ->required(),
                    ]),

                Section::make('Description')
                    ->schema([
                        TextInput::make('short_description')
                            ->required(),
                        TextInput::make('short_description_kh')
                            ->default(null),
                        RichEditor::make('description')
                            ->default(null)
                            ->columnSpanFull(),
                        RichEditor::make('description_kh')
                            ->default(null)
                            ->columnSpanFull(),
                        RichEditor::make('faqs')
                            ->label('FAQ')
                            ->default(null)
                            ->columnSpanFull(),
                    ]),

                Section::make('Media')
                    ->schema([
                        FileUpload::make('images')
                            ->label('Service Images')
                            ->multiple()
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '16:9',
                                '4:3',
                                '1:1',
                            ])
                            ->directory('services')
                            ->visibility('public')
                            ->maxFiles(5)
                            ->reorderable(),
                        FileUpload::make('portfolio')
                            ->label('Portfolio Images')
                            ->multiple()
                            ->image()
                            ->directory('services/portfolio')
                            ->visibility('public')
                            ->maxFiles(20)
                            ->reorderable(),
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
                                            ->directory('services/og')
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
