<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

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

                Section::make('Ordered Items')
                    ->description('Products captured at checkout or entered manually for this order.')
                    ->schema([
                        TextEntry::make('items_summary')
                            ->hiddenLabel()
                            ->state(function ($record): HtmlString {
                                $items = $record->items;

                                if ($items->isEmpty()) {
                                    return new HtmlString('<span class="text-gray-500">No ordered items recorded.</span>');
                                }

                                $rows = $items->map(function ($item): string {
                                    $name = e($item->product_name);
                                    $sku = e($item->product_sku);
                                    $unit = e($item->product_unit);
                                    $quantity = number_format((float) $item->quantity);
                                    $unitPrice = number_format((float) $item->unit_price, 2);
                                    $lineTotal = number_format((float) $item->total_price, 2);

                                    return <<<HTML
                                        <li style="padding: 10px 0; border-bottom: 1px solid rgba(148, 163, 184, .28);">
                                            <div style="font-weight: 600;">{$name}</div>
                                            <div style="margin-top: 4px; color: rgb(100, 116, 139); font-size: 13px;">
                                                SKU: {$sku} · Qty: {$quantity} {$unit} · Unit: \${$unitPrice} · Total: \${$lineTotal}
                                            </div>
                                        </li>
                                    HTML;
                                })->implode('');

                                return new HtmlString("<ul style=\"margin: 0; padding: 0; list-style: none;\">{$rows}</ul>");
                            })
                            ->html(),
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
