<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Order;
use App\Models\Product;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer Information')
                    ->description('Start with the customer and contact details.')
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->default(null),
                        TextInput::make('order_number')
                            ->required()
                            ->default(fn (): string => Order::generateNumber()),
                        TextInput::make('customer_name')
                            ->required(),
                        TextInput::make('customer_phone')
                            ->tel()
                            ->required(),
                        TextInput::make('customer_email')
                            ->email()
                            ->default(null),
                    ]),

                Section::make('Delivery Details')
                    ->description('Optional delivery preferences can be completed when they are known.')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Select::make('delivery_method')
                            ->options([
                                'delivery' => 'Delivery',
                                'pickup' => 'Pickup',
                            ])
                            ->default('delivery'),
                        TextInput::make('delivery_area')
                            ->default(null),
                        Textarea::make('delivery_address')
                            ->default(null)
                            ->columnSpanFull(),
                        TextInput::make('map_url')
                            ->url()
                            ->default(null),
                        Select::make('timing')
                            ->options([
                                'standard' => 'Standard',
                                'express' => 'Express',
                            ])
                            ->default('standard'),
                        DatePicker::make('preferred_date'),
                        Select::make('support_type')
                            ->options([
                                'none' => 'None',
                                'installation' => 'Installation',
                                'assembly' => 'Assembly',
                            ])
                            ->default('none'),
                    ]),

                Section::make('Financial')
                    ->description('Totals update automatically from ordered items and the delivery fee.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('subtotal')
                            ->required()
                            ->numeric()
                            ->prefix('$'),
                        TextInput::make('delivery_fee')
                            ->required()
                            ->numeric()
                            ->default(0.0)
                            ->prefix('$')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set): null => self::refreshOrderTotals($get, $set, '')),
                        TextInput::make('total_amount')
                            ->required()
                            ->numeric()
                            ->prefix('$'),
                        TextInput::make('currency')
                            ->required()
                            ->default('USD'),
                    ]),

                Section::make('Ordered Items')
                    ->description('Select products to fill item details, then adjust quantity or pricing if needed.')
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->addActionLabel('Add ordered item')
                            ->defaultItems(0)
                            ->hiddenLabel()
                            ->reorderable(false)
                            ->collapsible()
                            ->compact()
                            ->itemLabel(fn (array $state): ?string => $state['product_name'] ?? null)
                            ->columns(12)
                            ->schema([
                                Select::make('product_id')
                                    ->label('Product')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                                        if (! $state) {
                                            return;
                                        }

                                        $product = Product::query()
                                            ->select('id', 'name', 'sku', 'unit', 'price')
                                            ->find($state);

                                        if (! $product) {
                                            return;
                                        }

                                        $set('product_name', $product->name);
                                        $set('product_sku', $product->sku);
                                        $set('product_unit', $product->unit);
                                        $set('unit_price', (float) $product->price);
                                        $set('quantity', 1);
                                        $set('total_price', (float) $product->price);
                                        self::refreshOrderTotals($get, $set);
                                    })
                                    ->placeholder('Select product')
                                    ->columnSpan(6),
                                Hidden::make('product_name')
                                    ->required(),
                                Hidden::make('product_sku')
                                    ->required(),
                                Hidden::make('product_unit')
                                    ->required()
                                    ->default('unit'),
                                TextInput::make('quantity')
                                    ->label('Qty')
                                    ->required()
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set): void {
                                        $quantity = max(1, (int) $get('quantity'));
                                        $unitPrice = max(0, (float) $get('unit_price'));

                                        $set('total_price', round($quantity * $unitPrice, 2));
                                        self::refreshOrderTotals($get, $set);
                                    })
                                    ->columnSpan(2),
                                TextInput::make('unit_price')
                                    ->label('Unit price')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set): void {
                                        $quantity = max(1, (int) $get('quantity'));
                                        $unitPrice = max(0, (float) $get('unit_price'));

                                        $set('total_price', round($quantity * $unitPrice, 2));
                                        self::refreshOrderTotals($get, $set);
                                    })
                                    ->columnSpan(2),
                                TextInput::make('total_price')
                                    ->label('Line total')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set): null => self::refreshOrderTotals($get, $set))
                                    ->columnSpan(2),
                            ]),
                    ]),

                Section::make('Status & Tracking')
                    ->description('Operational tracking fields. Defaults are suitable for a new order.')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'processing' => 'Processing',
                                'ready_to_ship' => 'Ready to Ship',
                                'shipped' => 'Shipped',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                                'refunded' => 'Refunded',
                            ])
                            ->required()
                            ->default('pending'),
                        Select::make('payment_status')
                            ->options([
                                'unpaid' => 'Unpaid',
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                                'failed' => 'Failed',
                                'refunded' => 'Refunded',
                            ])
                            ->required()
                            ->default('unpaid'),
                        DateTimePicker::make('ordered_at')
                            ->required()
                            ->default(now()),
                        DateTimePicker::make('confirmed_at'),
                        DateTimePicker::make('shipped_at'),
                        DateTimePicker::make('completed_at'),
                        DateTimePicker::make('cancelled_at'),
                    ]),

                Section::make('Notes')
                    ->description('Optional customer and internal notes.')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Textarea::make('notes')
                            ->default(null)
                            ->rows(3),
                        Textarea::make('admin_notes')
                            ->default(null)
                            ->rows(3),
                    ]),
            ]);
    }

    private static function refreshOrderTotals(Get $get, Set $set, string $parentPath = '../../'): null
    {
        $items = $get("{$parentPath}items") ?? [];
        $subtotal = collect($items)->sum(fn (array $item): float => (float) ($item['total_price'] ?? 0));
        $deliveryFee = (float) ($get("{$parentPath}delivery_fee") ?? 0);

        $set("{$parentPath}subtotal", round($subtotal, 2));
        $set("{$parentPath}total_amount", round($subtotal + $deliveryFee, 2));

        return null;
    }
}
