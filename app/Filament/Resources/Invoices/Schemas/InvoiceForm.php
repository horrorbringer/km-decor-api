<?php

namespace App\Filament\Resources\Invoices\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Invoice Details')
                    ->columns(2)
                    ->schema([
                        Select::make('order_id')
                            ->label('Order')
                            ->relationship('order', 'order_number')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('invoice_number')
                            ->required()
                            ->maxLength(50),
                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                                'cancelled' => 'Cancelled',
                                'refunded' => 'Refunded',
                            ])
                            ->required(),
                        DateTimePicker::make('issued_at'),
                        DateTimePicker::make('due_at'),
                        DateTimePicker::make('paid_at'),
                    ]),

                Section::make('Financial')
                    ->columns(2)
                    ->schema([
                        TextInput::make('subtotal')
                            ->required()
                            ->numeric()
                            ->prefix('$'),
                        TextInput::make('tax_rate')
                            ->label('Tax Rate (%)')
                            ->numeric()
                            ->step(0.01)
                            ->suffix('%'),
                        TextInput::make('tax_amount')
                            ->numeric()
                            ->prefix('$'),
                        TextInput::make('delivery_fee')
                            ->numeric()
                            ->prefix('$'),
                        TextInput::make('total_amount')
                            ->required()
                            ->numeric()
                            ->prefix('$'),
                        TextInput::make('currency')
                            ->required()
                            ->default('USD')
                            ->maxLength(3),
                    ]),

                Section::make('Notes')
                    ->schema([
                        Textarea::make('notes')
                            ->rows(3),
                    ]),
            ]);
    }
}
