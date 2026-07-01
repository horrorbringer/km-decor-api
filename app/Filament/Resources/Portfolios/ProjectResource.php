<?php

namespace App\Filament\Resources\Portfolios;

use App\Filament\Resources\Portfolios\Pages\CreateProject;
use App\Filament\Resources\Portfolios\Pages\EditProject;
use App\Filament\Resources\Portfolios\Pages\ListProjects;
use App\Filament\Resources\Portfolios\Pages\ViewProject;
use App\Filament\Resources\Portfolios\Schemas\ProjectForm;
use App\Filament\Resources\Portfolios\Schemas\ProjectInfolist;
use App\Filament\Resources\Portfolios\Tables\ProjectsTable;
use App\Models\Project;
use BackedEnum;
use Filament\Resources\Resource;
use UnitEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static UnitEnum|string|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordRouteKeyName = 'id';

    public static function getNavigationLabel(): string
    {
        return 'Portfolios';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Portfolios';
    }

    public static function getModelLabel(): string
    {
        return 'Portfolio';
    }

    public static function form(Schema $schema): Schema
    {
        return ProjectForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProjectInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProjectsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view_portfolios') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create_portfolios') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('update_portfolios') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete_portfolios') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('delete_portfolios') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'view' => ViewProject::route('/{record}'),
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }
}
