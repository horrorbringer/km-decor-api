<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account Information')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required(),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->required(),
                        TextInput::make('phone')
                            ->tel()
                            ->default(null),
                        TextInput::make('password')
                            ->password()
                            ->required(fn ($livewire) => $livewire instanceof CreateRecord)
                            ->helperText('Leave blank to keep the current password.')
                            ->dehydrateStateUsing(fn ($state) => bcrypt($state))
                            ->dehydrated(fn ($state) => filled($state)),
                    ]),

                Section::make('Permissions & Status')
                    ->columns(2)
                    ->schema([
                        Select::make('roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->options(Role::all()->pluck('name', 'name'))
                            ->label('Roles'),
                        Toggle::make('is_active')
                            ->required(),
                        Toggle::make('email_verified_at')
                            ->label('Email verified')
                            ->helperText('Use only when staff has confirmed the customer email manually.')
                            ->afterStateHydrated(fn ($component, $state) => $component->state(filled($state)))
                            ->dehydrateStateUsing(fn ($state) => $state ? now() : null),
                        DateTimePicker::make('last_login_at'),
                    ]),
            ]);
    }
}
