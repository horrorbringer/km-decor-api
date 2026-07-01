<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer Information')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('order_number'),
                        TextEntry::make('user.name')
                            ->label('User')
                            ->placeholder('-'),
                        TextEntry::make('customer_name'),
                        TextEntry::make('customer_phone'),
                        TextEntry::make('customer_email')
                            ->placeholder('-'),
                    ]),

                Section::make('Delivery Details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('delivery_method')
                            ->badge(),
                        TextEntry::make('delivery_area')
                            ->placeholder('-'),
                        TextEntry::make('delivery_address')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('map_url')
                            ->placeholder('-'),
                        TextEntry::make('timing')
                            ->badge()
                            ->color(fn (?string $state): string => $state === 'express' ? 'warning' : 'gray'),
                        TextEntry::make('preferred_date')
                            ->date()
                            ->placeholder('-'),
                        TextEntry::make('support_type')
                            ->placeholder('-'),
                    ]),

                Section::make('Financial')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('subtotal')
                            ->money('USD'),
                        TextEntry::make('delivery_fee')
                            ->money('USD'),
                        TextEntry::make('total_amount')
                            ->money('USD'),
                        TextEntry::make('currency'),
                    ]),

                Section::make('Status & Tracking')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'confirmed' => 'info',
                                'processing' => 'primary',
                                'ready_to_ship' => 'info',
                                'shipped' => 'success',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                'refunded' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('payment_status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'paid' => 'success',
                                'pending' => 'warning',
                                'unpaid' => 'danger',
                                'failed' => 'danger',
                                'refunded' => 'info',
                                default => 'gray',
                            }),
                        TextEntry::make('ordered_at')
                            ->dateTime(),
                        TextEntry::make('confirmed_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('shipped_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('completed_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('cancelled_at')
                            ->dateTime()
                            ->placeholder('-'),
                    ]),

                Section::make('Notes')
                    ->schema([
                        TextEntry::make('notes')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('admin_notes')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),

                Section::make('Metadata')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id')
                            ->label('ID'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ]),
            ]);
    }
}
