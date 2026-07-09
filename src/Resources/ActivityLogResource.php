<?php

namespace MKWebDesign\FilamentWatchdog\Resources;

use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Actions;
use Filament\Tables\Table;
use MKWebDesign\FilamentWatchdog\Models\ActivityLog;
use MKWebDesign\FilamentWatchdog\Resources\ActivityLogResource\Pages;
use MKWebDesign\FilamentWatchdog\Traits\ConfiguresWatchdogNavigation;

class ActivityLogResource extends Resource
{
    use ConfiguresWatchdogNavigation;

    protected static function getNavigationVisibility(): string
    {
        return 'conditional';
    }

    protected static function getDefaultSecuritySort(): int
    {
        return 3;
    }

    protected static ?string $model = ActivityLog::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static string|\UnitEnum|null $navigationGroup = null;
    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('filament-watchdog-v5::messages.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-watchdog-v5::messages.resource.activity_log.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('filament-watchdog-v5::messages.resource.activity_log.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-watchdog-v5::messages.resource.activity_log.plural_model_label');
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('event_type')
                    ->label(__('filament-watchdog-v5::messages.resource.activity_log.fields.event_type'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('user_id')
                    ->label(__('filament-watchdog-v5::messages.resource.activity_log.fields.user_id'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('ip_address')
                    ->label(__('filament-watchdog-v5::messages.resource.activity_log.fields.ip_address'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('user_agent')
                    ->label(__('filament-watchdog-v5::messages.resource.activity_log.fields.user_agent'))
                    ->maxLength(255),
                Forms\Components\Textarea::make('event_details')
                    ->label(__('filament-watchdog-v5::messages.resource.activity_log.fields.event_details'))
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Select::make('risk_level')
                    ->label(__('filament-watchdog-v5::messages.resource.activity_log.fields.risk_level'))
                    ->options([
                        'low' => __('filament-watchdog-v5::messages.risk_level.low'),
                        'medium' => __('filament-watchdog-v5::messages.risk_level.medium'),
                        'high' => __('filament-watchdog-v5::messages.risk_level.high'),
                        'critical' => __('filament-watchdog-v5::messages.risk_level.critical'),
                    ])
                    ->required(),
                Forms\Components\Textarea::make('metadata')
                    ->label(__('filament-watchdog-v5::messages.resource.activity_log.fields.metadata'))
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('event_type')
                    ->label(__('filament-watchdog-v5::messages.resource.activity_log.fields.event_type'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('user_id')
                    ->label(__('filament-watchdog-v5::messages.resource.activity_log.fields.user_id'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label(__('filament-watchdog-v5::messages.resource.activity_log.fields.ip_address'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('risk_level')
                    ->label(__('filament-watchdog-v5::messages.resource.activity_log.fields.risk_level'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('filament-watchdog-v5::messages.risk_level.' . $state))
                    ->color(fn (string $state): string => match ($state) {
                        'low'      => 'success',
                        'medium'   => 'info',
                        'high'     => 'warning',
                        'critical' => 'danger',
                        default    => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament-watchdog-v5::messages.common.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('filament-watchdog-v5::messages.common.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event_type')
                    ->label(__('filament-watchdog-v5::messages.resource.activity_log.fields.event_type')),
                Tables\Filters\SelectFilter::make('risk_level')
                    ->label(__('filament-watchdog-v5::messages.resource.activity_log.fields.risk_level'))
                    ->options([
                        'low' => __('filament-watchdog-v5::messages.risk_level.low'),
                        'medium' => __('filament-watchdog-v5::messages.risk_level.medium'),
                        'high' => __('filament-watchdog-v5::messages.risk_level.high'),
                        'critical' => __('filament-watchdog-v5::messages.risk_level.critical'),
                    ]),
            ])
            ->actions([
                Actions\ViewAction::make(),
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
            'index' => Pages\ListActivityLogs::route('/'),
            'view' => Pages\ViewActivityLog::route('/{record}'),
        ];
    }
}
