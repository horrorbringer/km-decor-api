<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockProducts extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->where('stock_qty', '<', 10)
                    ->where('status', 'published')
                    ->orderBy('stock_qty')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sku')
                    ->label('SKU'),
                TextColumn::make('stock_qty')
                    ->numeric()
                    ->color(fn ($state): ?string => match (true) {
                        $state === 0 => 'danger',
                        $state < 5 => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('price')
                    ->money('USD'),
            ]);
    }

    public static function getHeading(): string
    {
        return 'Low Stock Products';
    }
}
