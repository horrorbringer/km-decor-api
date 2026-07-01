<?php

namespace App\Filament\Resources\ServiceInquiries;

use App\Filament\Resources\ServiceInquiries\Pages\ManageServiceInquiries;
use App\Models\ServiceInquiry;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ServiceInquiryResource extends Resource
{
    protected static ?string $model = ServiceInquiry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static UnitEnum|string|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['service', 'user']))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('service.name')
                    ->label('Service')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'gray',
                        'read' => 'info',
                        'contacted' => 'warning',
                        'quoted' => 'primary',
                        'closed' => 'success',
                        'lost' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('budget_range')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('preferred_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('assigned_to')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'new' => 'New',
                        'read' => 'Read',
                        'contacted' => 'Contacted',
                        'quoted' => 'Quoted',
                        'closed' => 'Closed',
                        'lost' => 'Lost',
                    ]),
                SelectFilter::make('type')
                    ->options([
                        'quote' => 'Quote',
                        'contact' => 'Contact',
                        'booking' => 'Booking',
                    ]),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageServiceInquiries::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view_inquiries') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('update_inquiries') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('update_inquiries') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('update_inquiries') ?? false;
    }
}
