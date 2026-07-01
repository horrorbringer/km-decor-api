<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required(),
                Select::make('brand_id')
                    ->relationship('brand', 'name'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('name_kh')
                    ->default(null),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('sku')
                    ->label('SKU')
                    ->required(),
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
                    ->required(),
                Toggle::make('requires_installation')
                    ->required(),
                TextInput::make('warranty_months')
                    ->numeric()
                    ->default(null),
                TextInput::make('avg_rating')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('review_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_featured')
                    ->required(),
                Toggle::make('is_new')
                    ->required(),
                Toggle::make('is_best_seller')
                    ->required(),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('status')
                    ->required()
                    ->default('draft'),
                DateTimePicker::make('published_at'),
                FileUpload::make('images')
                    ->label('Product Images')
                    ->multiple()
                    ->image()
                    ->imageEditor()
                    ->imageEditorAspectRatios([
                        '16:9',
                        '4:3',
                        '1:1',
                    ])
                    ->directory('products')
                    ->visibility('public')
                    ->maxFiles(10)
                    ->reorderable()
                    ->columnSpanFull(),

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
                                    ->directory('products/og')
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
            ]);
    }
}
