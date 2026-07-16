<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Support\AdminSeo;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->description('Create the category with its name, type, and visibility. Other content is optional.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state): void {
                                if (blank($state)) {
                                    return;
                                }

                                if (blank($get('slug')) || $get('slug') === Str::slug($old ?? '')) {
                                    $set('slug', Str::slug($state));
                                }

                                AdminSeo::syncTitle($get, $set, $old, $state, 'CollectionPage');
                            }),
                        TextInput::make('name_kh')
                            ->default(null),
                        TextInput::make('slug')
                            ->required()
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Str::slug($state) : null),
                        Select::make('parent_id')
                            ->relationship('parent', 'name'),
                        TextInput::make('type')
                            ->required()
                            ->default('product'),
                        TextInput::make('icon')
                            ->default(null),
                        TextInput::make('sort_order')
                            ->required()
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_active')
                            ->required()
                            ->default(true),
                        Toggle::make('is_featured')
                            ->required()
                            ->default(false),
                    ]),

                Section::make('Image & Description')
                    ->description('Add storefront presentation content when it is available.')
                    ->collapsible()
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('image')
                            ->label('Category Image')
                            ->collection('image')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '16:9',
                                '4:3',
                                '1:1',
                            ])
                            ->maxFiles(1),
                        RichEditor::make('description')
                            ->default(null)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set, ?string $old, ?string $state) => AdminSeo::syncDescription($get, $set, $old, $state, 'CollectionPage'))
                            ->columnSpanFull(),
                    ]),

                Section::make('SEO & Metadata')
                    ->description('Optional advanced metadata for search and social sharing.')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Tabs::make('SEO')
                            ->tabs([
                                Tab::make('Meta')
                                    ->schema([
                                        TextInput::make('meta_title')
                                            ->label('Meta Title')
                                            ->maxLength(60)
                                            ->helperText('Generated from the category name until you customize it.'),
                                        Textarea::make('meta_description')
                                            ->label('Meta Description')
                                            ->rows(3)
                                            ->maxLength(160)
                                            ->helperText('Generated from the category description until you customize it.'),
                                        FileUpload::make('og_image')
                                            ->label('Open Graph Image')
                                            ->image()
                                            ->directory('categories/og')
                                            ->visibility('public')
                                            ->maxFiles(1)
                                            ->columnSpanFull(),
                                    ]),
                                Tab::make('Structured Data')
                                    ->schema([
                                        KeyValue::make('structured_data')
                                            ->label('JSON-LD Structured Data')
                                            ->helperText('Schema type, name, and description update automatically. Custom keys are preserved.')
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
