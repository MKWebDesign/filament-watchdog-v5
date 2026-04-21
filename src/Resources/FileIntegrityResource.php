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
    protected static string|\UnitEnum|null $navigationGroup = 'Security';
    protected static ?string $navigationLabel = 'File Integrity';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('file_path')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('file_hash')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('file_size')
                    ->required()
                    ->numeric(),
                Forms\Components\DateTimePicker::make('last_modified')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'clean' => 'Clean',
                        'modified' => 'Modified',
                        'deleted' => 'Deleted',
                        'new' => 'New',
                    ])
                    ->required(),
                Forms\Components\Textarea::make('changes')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('file_path')
                    ->searchable(),
                Tables\Columns\TextColumn::make('file_hash')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('file_size')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_modified')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'clean'    => 'success',
                        'modified' => 'warning',
                        'deleted'  => 'danger',
                        'new'      => 'info',
                        default    => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'clean' => 'Clean',
                        'modified' => 'Modified',
                        'deleted' => 'Deleted',
                        'new' => 'New',
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
