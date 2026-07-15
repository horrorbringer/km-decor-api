<?php

namespace App\Filament\Resources\Invoices\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvoiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Invoice Information')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('invoice_number'),
                        TextEntry::make('order.order_number')
                            ->label('Order Number')
                            ->url(fn ($record) => "../orders/{$record->order_id}"),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft' => 'gray',
                                'pending' => 'warning',
                                'paid' => 'success',
                                'cancelled' => 'danger',
                                'refunded' => 'info',
                                default => 'gray',
                            }),
                        TextEntry::make('order.customer_name')
                            ->label('Customer'),
                        TextEntry::make('order.customer_email')
                            ->label('Email'),
                        TextEntry::make('order.customer_phone')
                            ->label('Phone'),
                    ]),

                Section::make('Financial Breakdown')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('subtotal')
                            ->money('USD'),
                        TextEntry::make('tax_rate')
                            ->label('Tax Rate')
                            ->suffix('%'),
                        TextEntry::make('tax_amount')
                            ->money('USD'),
                        TextEntry::make('delivery_fee')
                            ->money('USD'),
                        TextEntry::make('total_amount')
                            ->money('USD')
                            ->weight('bold')
                            ->size('lg'),
                        TextEntry::make('currency'),
                    ]),

                Section::make('Timeline')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('issued_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('due_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('paid_at')
                            ->dateTime()
                            ->placeholder('-'),
                    ]),

                Section::make('Notes')
                    ->schema([
                        TextEntry::make('notes')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
