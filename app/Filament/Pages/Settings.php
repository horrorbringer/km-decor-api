<?php

namespace App\Filament\Pages;

use App\Settings\GeneralSettings;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Settings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static UnitEnum|string|null $navigationGroup = 'Users & Access';

    protected static ?int $navigationSort = 3;

    protected static string $settings = GeneralSettings::class;

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->components([
                Section::make('Site Information')
                    ->columns(2)
                    ->schema([
                        TextInput::make('site_name')
                            ->label('Site Name')
                            ->required(),
                        TextInput::make('site_description')
                            ->label('Site Description')
                            ->columnSpanFull(),
                        TextInput::make('contact_email')
                            ->label('Contact Email')
                            ->email(),
                        TextInput::make('contact_phone')
                            ->label('Contact Phone'),
                        TextInput::make('address')
                            ->label('Address')
                            ->columnSpanFull(),
                        TextInput::make('frontend_url')
                            ->label('Frontend URL')
                            ->url()
                            ->columnSpanFull(),
                    ]),
                Section::make('Social Links')
                    ->columns(2)
                    ->schema([
                        TextInput::make('facebook_url')
                            ->label('Facebook URL')
                            ->url(),
                        TextInput::make('instagram_url')
                            ->label('Instagram URL')
                            ->url(),
                        TextInput::make('telegram_url')
                            ->label('Telegram URL')
                            ->url(),
                        TextInput::make('youtube_url')
                            ->label('YouTube URL')
                            ->url(),
                    ]),
                Section::make('Branding')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('logo_url')
                            ->label('Logo')
                            ->image()
                            ->directory('branding'),
                        FileUpload::make('favicon_url')
                            ->label('Favicon')
                            ->image()
                            ->directory('branding'),
                    ]),
                Section::make('Admin Panel Colors')
                    ->columns(3)
                    ->schema([
                        \Filament\Forms\Components\ColorPicker::make('color_primary')
                            ->label('Primary'),
                        \Filament\Forms\Components\ColorPicker::make('color_danger')
                            ->label('Danger'),
                        \Filament\Forms\Components\ColorPicker::make('color_success')
                            ->label('Success'),
                        \Filament\Forms\Components\ColorPicker::make('color_warning')
                            ->label('Warning'),
                        \Filament\Forms\Components\ColorPicker::make('color_info')
                            ->label('Info'),
                    ]),
            ]);
    }

    public static function getNavigationLabel(): string
    {
        return 'Site Settings';
    }

    public function canEdit(): bool
    {
        return auth()->user()?->can('manage_settings') ?? false;
    }
}
