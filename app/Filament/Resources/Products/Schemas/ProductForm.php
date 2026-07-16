<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Support\AdminSeo;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
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

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Quick product setup')
                    ->description('Enter the minimum information needed to save a useful draft. Complete content, merchandising, and SEO later.')
                    ->columns(2)
                    ->schema([
                        Select::make('category_id')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('brand_id')
                            ->relationship('brand', 'name'),
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

                                AdminSeo::syncTitle($get, $set, $old, $state, 'Product');
                            }),
                        TextInput::make('name_kh')
                            ->default(null),
                        TextInput::make('slug')
                            ->required()
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Str::slug($state) : null),
                        TextInput::make('sku')
                            ->label('SKU')
                            ->required(),
                        TextInput::make('short_description')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set, ?string $old, ?string $state) => AdminSeo::syncDescription($get, $set, $old, $state, 'Product'))
                            ->columnSpanFull(),
                    ]),

                Section::make('Customer content')
                    ->description('Optional detail content for the product page. Staff can return to this after the product draft is saved.')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
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
                        Textarea::make('customer_goal')
                            ->rows(3)
                            ->default(null)
                            ->columnSpanFull(),
                        Repeater::make('features')
                            ->simple(
                                TextInput::make('feature')
                                    ->required()
                                    ->maxLength(255),
                            )
                            ->default([])
                            ->addActionLabel('Add feature')
                            ->columnSpanFull(),
                        Repeater::make('applications')
                            ->simple(
                                TextInput::make('application')
                                    ->required()
                                    ->maxLength(255),
                            )
                            ->default([])
                            ->addActionLabel('Add application')
                            ->columnSpanFull(),
                        Repeater::make('material_notes')
                            ->simple(
                                TextInput::make('note')
                                    ->required()
                                    ->maxLength(255),
                            )
                            ->default([])
                            ->addActionLabel('Add material note')
                            ->columnSpanFull(),
                        TextInput::make('lead_time')
                            ->default(null)
                            ->maxLength(120),
                        TextInput::make('delivery_note')
                            ->default(null)
                            ->maxLength(160),
                        Repeater::make('compatible_product_slugs')
                            ->simple(
                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255),
                            )
                            ->default([])
                            ->addActionLabel('Add compatible product slug')
                            ->columnSpanFull(),
                        Textarea::make('specifications')
                            ->default(null)
                            ->columnSpanFull(),
                        Textarea::make('tags')
                            ->default(null)
                            ->columnSpanFull(),
                    ]),

                Section::make('Pricing & inventory')
                    ->description('The required commercial fields used by catalog, cart, and availability displays.')
                    ->columns(3)
                    ->schema([
                        TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('$'),
                        TextInput::make('compare_price')
                            ->numeric()
                            ->default(null)
                            ->prefix('$'),
                        TextInput::make('currency')
                            ->required()
                            ->default('USD'),
                        TextInput::make('unit')
                            ->required(),
                        TextInput::make('min_order_qty')
                            ->required()
                            ->numeric()
                            ->default(1),
                        TextInput::make('stock_qty')
                            ->required()
                            ->numeric()
                            ->default(0),
                        Toggle::make('allow_backorder')
                            ->required()
                            ->default(false),
                        Toggle::make('requires_installation')
                            ->required()
                            ->default(false),
                        TextInput::make('warranty_months')
                            ->numeric()
                            ->default(null),
                    ]),

                Section::make('Merchandising & publishing')
                    ->description('Optional badges, ordering, and publication controls. New records remain drafts by default.')
                    ->columns(3)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextInput::make('avg_rating')
                            ->required()
                            ->numeric()
                            ->default(0.0),
                        TextInput::make('review_count')
                            ->required()
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_featured')
                            ->required()
                            ->default(false),
                        Toggle::make('is_new')
                            ->required()
                            ->default(false),
                        Toggle::make('is_best_seller')
                            ->required()
                            ->default(false),
                        TextInput::make('sort_order')
                            ->required()
                            ->numeric()
                            ->default(0),
                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'published' => 'Published',
                                'archived' => 'Archived',
                            ])
                            ->native(false)
                            ->required()
                            ->default('draft'),
                        DateTimePicker::make('published_at'),
                    ]),

                Section::make('Product images')
                    ->description('Upload the clearest product image first. Additional images become the storefront gallery.')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('images')
                            ->label('Product Images')
                            ->collection('images')
                            ->multiple()
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '16:9',
                                '4:3',
                                '1:1',
                            ])
                            ->maxFiles(10)
                            ->reorderable()
                            ->columnSpanFull(),
                    ]),

                Section::make('SEO & structured data')
                    ->description('Advanced search and social-sharing metadata. Defaults are sufficient for a draft.')
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
                                            ->helperText('Generated from the product name until you customize it.'),
                                        Textarea::make('meta_description')
                                            ->label('Meta Description')
                                            ->rows(3)
                                            ->maxLength(160)
                                            ->helperText('Generated from the short description until you customize it.'),
                                        FileUpload::make('og_image')
                                            ->label('Open Graph Image')
                                            ->image()
                                            ->directory('products/og')
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
