<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer Information')
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->default(null),
                        TextInput::make('order_number')
                            ->required(),
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
                    ->columns(2)
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
                            ->prefix('$'),
                        TextInput::make('total_amount')
                            ->required()
                            ->numeric()
                            ->prefix('$'),
                        TextInput::make('currency')
                            ->required()
                            ->default('USD'),
                    ]),

                Section::make('Status & Tracking')
                    ->columns(2)
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
                            ->required(),
                        DateTimePicker::make('confirmed_at'),
                        DateTimePicker::make('shipped_at'),
                        DateTimePicker::make('completed_at'),
                        DateTimePicker::make('cancelled_at'),
                    ]),

                Section::make('Notes')
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
}
