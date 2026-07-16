<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Models\Invoice;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Invoice Details')
                    ->description('Link the order and confirm the invoice identity first.')
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
                            ->default(fn (): string => Invoice::generateNumber())
                            ->maxLength(50),
                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                                'cancelled' => 'Cancelled',
                                'refunded' => 'Refunded',
                            ])
                            ->required()
                            ->default('draft'),
                        DateTimePicker::make('issued_at')
                            ->default(now()),
                        DateTimePicker::make('due_at')
                            ->default(now()->addDays((int) config('invoices.due_days', 30))),
                        DateTimePicker::make('paid_at'),
                    ]),

                Section::make('Financial')
                    ->description('Review calculated amounts before saving.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('subtotal')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set): null => self::refreshTotals($get, $set)),
                        TextInput::make('tax_rate')
                            ->label('Tax Rate (%)')
                            ->numeric()
                            ->step(0.01)
                            ->suffix('%')
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set): null => self::refreshTotals($get, $set)),
                        TextInput::make('tax_amount')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->readOnly(),
                        TextInput::make('delivery_fee')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set): null => self::refreshTotals($get, $set)),
                        TextInput::make('total_amount')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->readOnly(),
                        TextInput::make('currency')
                            ->required()
                            ->default('USD')
                            ->maxLength(3),
                    ]),

                Section::make('Notes')
                    ->description('Optional invoice notes and terms.')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Textarea::make('notes')
                            ->rows(3),
                    ]),
            ]);
    }

    private static function refreshTotals(Get $get, Set $set): null
    {
        $subtotal = max(0, (float) ($get('subtotal') ?? 0));
        $taxRate = max(0, (float) ($get('tax_rate') ?? 0));
        $deliveryFee = max(0, (float) ($get('delivery_fee') ?? 0));
        $taxAmount = round($subtotal * ($taxRate / 100), 2);

        $set('tax_amount', $taxAmount);
        $set('total_amount', round($subtotal + $taxAmount + $deliveryFee, 2));

        return null;
    }
}
