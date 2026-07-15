<?php

namespace App\Filament\Resources\Portfolios\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\ToolbarButtonGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state): void {
                                if (blank($state)) {
                                    return;
                                }

                                if (filled($get('slug')) && $get('slug') !== Str::slug($old ?? '')) {
                                    return;
                                }

                                $set('slug', Str::slug($state));
                            }),
                        TextInput::make('title_kh')
                            ->default(null),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(160)
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Str::slug($state) : null),
                        TextInput::make('setting')
                            ->placeholder('e.g. Residential interior')
                            ->default(null),
                        TextInput::make('focus')
                            ->placeholder('e.g. Ceiling and lighting')
                            ->default(null),
                        TextInput::make('sort_order')
                            ->required()
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_featured')
                            ->label('Featured')
                            ->helperText('Show this project in featured portfolio sections.')
                            ->default(false),
                        Select::make('status')
                            ->required()
                            ->options([
                                'draft' => 'Draft',
                                'published' => 'Published',
                            ])
                            ->native(false)
                            ->default('draft'),
                        DateTimePicker::make('published_at')
                            ->label('Publish date')
                            ->helperText('Leave empty when publishing to make it visible immediately, or choose a future date to schedule.'),
                    ]),

                Section::make('Content')
                    ->schema([
                        RichEditor::make('goal')
                            ->label('Design goal')
                            ->default(null)
                            ->columnSpanFull()
                            ->toolbarButtons([
                                ['bold', 'italic', 'underline', 'strike', 'link'],
                                [ToolbarButtonGroup::make('Heading', ['paragraph', 'h1', 'h2', 'h3'])->textualButtons()],
                                [ToolbarButtonGroup::make('Align', ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'])],
                                [ToolbarButtonGroup::make('List', ['bulletList', 'orderedList'])],
                                ['blockquote', 'codeBlock'],
                                ['undo', 'redo'],
                            ]),
                        RichEditor::make('overview')
                            ->default(null)
                            ->columnSpanFull()
                            ->toolbarButtons([
                                ['bold', 'italic', 'underline', 'strike', 'link'],
                                [ToolbarButtonGroup::make('Heading', ['paragraph', 'h1', 'h2', 'h3'])->textualButtons()],
                                [ToolbarButtonGroup::make('Align', ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'])],
                                [ToolbarButtonGroup::make('List', ['bulletList', 'orderedList'])],
                                ['blockquote', 'codeBlock'],
                                ['undo', 'redo'],
                            ]),
                        RichEditor::make('challenge')
                            ->default(null)
                            ->columnSpanFull()
                            ->toolbarButtons([
                                ['bold', 'italic', 'underline', 'strike', 'link'],
                                [ToolbarButtonGroup::make('Heading', ['paragraph', 'h1', 'h2', 'h3'])->textualButtons()],
                                [ToolbarButtonGroup::make('Align', ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'])],
                                [ToolbarButtonGroup::make('List', ['bulletList', 'orderedList'])],
                                ['blockquote', 'codeBlock'],
                                ['undo', 'redo'],
                            ]),
                        RichEditor::make('response')
                            ->default(null)
                            ->columnSpanFull()
                            ->toolbarButtons([
                                ['bold', 'italic', 'underline', 'strike', 'link'],
                                [ToolbarButtonGroup::make('Heading', ['paragraph', 'h1', 'h2', 'h3'])->textualButtons()],
                                [ToolbarButtonGroup::make('Align', ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'])],
                                [ToolbarButtonGroup::make('List', ['bulletList', 'orderedList'])],
                                ['blockquote', 'codeBlock'],
                                ['undo', 'redo'],
                            ]),
                        Repeater::make('scope')
                            ->simple(
                                TextInput::make('item')
                                    ->required()
                                    ->maxLength(160),
                            )
                            ->default([])
                            ->addActionLabel('Add scope item')
                            ->columnSpanFull(),
                        Repeater::make('outcomes')
                            ->simple(
                                TextInput::make('item')
                                    ->required()
                                    ->maxLength(160),
                            )
                            ->default([])
                            ->addActionLabel('Add outcome')
                            ->columnSpanFull(),
                        Repeater::make('process')
                            ->label('Process steps')
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(120),
                                RichEditor::make('copy')
                                    ->required()
                                    ->columnSpanFull()
                                    ->toolbarButtons([
                                        ['bold', 'italic', 'underline', 'strike', 'link'],
                                        [ToolbarButtonGroup::make('Heading', ['paragraph', 'h1', 'h2', 'h3'])->textualButtons()],
                                        [ToolbarButtonGroup::make('Align', ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'])],
                                        [ToolbarButtonGroup::make('List', ['bulletList', 'orderedList'])],
                                        ['blockquote', 'codeBlock'],
                                        ['undo', 'redo'],
                                    ]),
                            ])
                            ->columns(2)
                            ->default([])
                            ->addActionLabel('Add step')
                            ->reorderable()
                            ->columnSpanFull(),
                    ]),

                Section::make('Relations')
                    ->columns(2)
                    ->schema([
                        Select::make('services')
                            ->multiple()
                            ->relationship('services', 'name')
                            ->preload(),
                        Select::make('products')
                            ->multiple()
                            ->relationship('products', 'name')
                            ->preload(),
                    ]),

                Section::make('Media')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('gallery')
                            ->label('Gallery Images')
                            ->collection('gallery')
                            ->multiple()
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '16:9',
                                '4:3',
                                '1:1',
                            ])
                            ->maxFiles(20)
                            ->reorderable()
                            ->columnSpanFull(),
                    ]),

                Section::make('SEO & Metadata')
                    ->schema([
                        Tabs::make('SEO')
                            ->tabs([
                                Tab::make('Meta')
                                    ->schema([
                                        TextInput::make('meta_title')
                                            ->label('Meta Title')
                                            ->maxLength(60)
                                            ->helperText('Max 60 characters for Google'),
                                        Textarea::make('meta_description')
                                            ->label('Meta Description')
                                            ->rows(3)
                                            ->maxLength(160)
                                            ->helperText('Max 160 characters for Google'),
                                        FileUpload::make('og_image')
                                            ->label('Open Graph Image')
                                            ->image()
                                            ->directory('projects/og')
                                            ->visibility('public')
                                            ->maxFiles(1)
                                            ->columnSpanFull(),
                                    ]),
                                Tab::make('Structured Data')
                                    ->schema([
                                        KeyValue::make('structured_data')
                                            ->label('JSON-LD Structured Data')
                                            ->keyLabel('Key')
                                            ->valueLabel('Value')
                                            ->addActionLabel('Add Property')
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
