<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Information')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('email')
                            ->label('Email address'),
                        TextEntry::make('phone')
                            ->placeholder('-'),
                        TextEntry::make('email_verified_at')
                            ->dateTime()
                            ->placeholder('-')
                            ->badge()
                            ->color(fn (?string $state): string => $state ? 'success' : 'danger'),
                        TextEntry::make('roles.name')
                            ->label('Roles')
                            ->badge()
                            ->separator(', '),
                        IconEntry::make('is_active')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),
                    ]),

                Section::make('Activity')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('last_login_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ]),
            ]);
    }
}
