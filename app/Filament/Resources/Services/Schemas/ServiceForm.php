<?php

namespace App\Filament\Resources\Services\Schemas;

use App\Support\AdminSeo;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\ToolbarButtonGroup;
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

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->description('Enter the service identity and visibility settings first.')
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

                                AdminSeo::syncTitle($get, $set, $old, $state, 'Service');
                            }),
                        TextInput::make('name_kh')
                            ->default(null),
                        TextInput::make('slug')
                            ->required()
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Str::slug($state) : null),
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
                            ->required()
                            ->default(true),
                        Toggle::make('is_featured')
                            ->required()
                            ->default(false),
                    ]),

                Section::make('Description')
                    ->description('A short description is enough for the initial save. Complete long-form content later.')
                    ->schema([
                        TextInput::make('short_description')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set, ?string $old, ?string $state) => AdminSeo::syncDescription($get, $set, $old, $state, 'Service')),
                        TextInput::make('short_description_kh')
                            ->default(null),
                        RichEditor::make('description')
                            ->default(null)
                            ->columnSpanFull()
                            ->toolbarButtons([
                                ['bold', 'italic', 'underline', 'strike', 'link'],
                                [ToolbarButtonGroup::make('Heading', ['paragraph', 'h1', 'h2', 'h3'])->textualButtons()],
                                [ToolbarButtonGroup::make('Align', ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'])],
                                [ToolbarButtonGroup::make('List', ['bulletList', 'orderedList'])],
                                ['blockquote', 'codeBlock'],
                                ['undo', 'redo'],
                            ]),
                        RichEditor::make('description_kh')
                            ->default(null)
                            ->columnSpanFull()
                            ->toolbarButtons([
                                ['bold', 'italic', 'underline', 'strike', 'link'],
                                [ToolbarButtonGroup::make('Heading', ['paragraph', 'h1', 'h2', 'h3'])->textualButtons()],
                                [ToolbarButtonGroup::make('Align', ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'])],
                                [ToolbarButtonGroup::make('List', ['bulletList', 'orderedList'])],
                                ['blockquote', 'codeBlock'],
                                ['undo', 'redo'],
                            ]),
                        RichEditor::make('faqs')
                            ->label('FAQ')
                            ->default(null)
                            ->columnSpanFull()
                            ->toolbarButtons([
                                ['bold', 'italic', 'underline', 'strike', 'link'],
                                [ToolbarButtonGroup::make('Heading', ['paragraph', 'h1', 'h2', 'h3'])->textualButtons()],
                                [ToolbarButtonGroup::make('Align', ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'])],
                                [ToolbarButtonGroup::make('List', ['bulletList', 'orderedList'])],
                                ['blockquote', 'codeBlock'],
                                ['undo', 'redo'],
                            ]),
                    ]),

                Section::make('Media')
                    ->description('Add the primary service image first. Portfolio images are optional.')
                    ->collapsible()
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('images')
                            ->label('Service Images')
                            ->collection('images')
                            ->multiple()
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '16:9',
                                '4:3',
                                '1:1',
                            ])
                            ->maxFiles(5)
                            ->reorderable(),
                        SpatieMediaLibraryFileUpload::make('portfolio')
                            ->label('Portfolio Images')
                            ->collection('portfolio')
                            ->multiple()
                            ->image()
                            ->maxFiles(20)
                            ->reorderable(),
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
                                            ->helperText('Generated from the service name until you customize it.'),
                                        Textarea::make('meta_description')
                                            ->label('Meta Description')
                                            ->rows(3)
                                            ->maxLength(160)
                                            ->helperText('Generated from the short description until you customize it.'),
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
