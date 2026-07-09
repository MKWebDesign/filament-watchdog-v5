<?php

namespace MKWebDesign\FilamentWatchdog\Resources;

use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Actions;
use Filament\Tables\Table;
use MKWebDesign\FilamentWatchdog\Models\FileIntegrityCheck;
use MKWebDesign\FilamentWatchdog\Resources\FileIntegrityResource\Pages;
use MKWebDesign\FilamentWatchdog\Traits\ConfiguresWatchdogNavigation;

class FileIntegrityResource extends Resource
{
    use ConfiguresWatchdogNavigation;

    protected static function getNavigationVisibility(): string
    {
        return 'conditional';
    }

    protected static function getDefaultSecuritySort(): int
    {
        return 1;
    }

    protected static ?string $model = FileIntegrityCheck::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static string|\UnitEnum|null $navigationGroup = null;
    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('filament-watchdog-v5::messages.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-watchdog-v5::messages.resource.file_integrity.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('filament-watchdog-v5::messages.resource.file_integrity.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-watchdog-v5::messages.resource.file_integrity.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('file_path')
                    ->label(__('filament-watchdog-v5::messages.resource.file_integrity.fields.file_path'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('file_hash')
                    ->label(__('filament-watchdog-v5::messages.resource.file_integrity.fields.file_hash'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('file_size')
                    ->label(__('filament-watchdog-v5::messages.resource.file_integrity.fields.file_size'))
                    ->required()
                    ->numeric(),
                Forms\Components\DateTimePicker::make('last_modified')
                    ->label(__('filament-watchdog-v5::messages.resource.file_integrity.fields.last_modified'))
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label(__('filament-watchdog-v5::messages.resource.file_integrity.fields.status'))
                    ->options([
                        'clean' => __('filament-watchdog-v5::messages.status.clean'),
                        'modified' => __('filament-watchdog-v5::messages.status.modified'),
                        'deleted' => __('filament-watchdog-v5::messages.status.deleted'),
                        'new' => __('filament-watchdog-v5::messages.status.new'),
                    ])
                    ->required(),
                Forms\Components\Textarea::make('changes')
                    ->label(__('filament-watchdog-v5::messages.resource.file_integrity.fields.changes'))
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('file_path')
                    ->label(__('filament-watchdog-v5::messages.resource.file_integrity.fields.file_path'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('file_hash')
                    ->label(__('filament-watchdog-v5::messages.resource.file_integrity.fields.file_hash'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('file_size')
                    ->label(__('filament-watchdog-v5::messages.resource.file_integrity.fields.file_size'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_modified')
                    ->label(__('filament-watchdog-v5::messages.resource.file_integrity.fields.last_modified'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('filament-watchdog-v5::messages.resource.file_integrity.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('filament-watchdog-v5::messages.status.' . $state))
                    ->color(fn (string $state): string => match ($state) {
                        'clean'    => 'success',
                        'modified' => 'warning',
                        'deleted'  => 'danger',
                        'new'      => 'info',
                        default    => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament-watchdog-v5::messages.common.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('filament-watchdog-v5::messages.common.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('filament-watchdog-v5::messages.resource.file_integrity.fields.status'))
                    ->options([
                        'clean' => __('filament-watchdog-v5::messages.status.clean'),
                        'modified' => __('filament-watchdog-v5::messages.status.modified'),
                        'deleted' => __('filament-watchdog-v5::messages.status.deleted'),
                        'new' => __('filament-watchdog-v5::messages.status.new'),
                    ]),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFileIntegrityChecks::route('/'),
            'create' => Pages\CreateFileIntegrityCheck::route('/create'),
            'edit' => Pages\EditFileIntegrityCheck::route('/{record}/edit'),
        ];
    }
}
